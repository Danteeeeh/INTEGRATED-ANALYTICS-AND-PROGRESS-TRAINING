<?php

namespace App\Services;

use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Closes assessment attempts whose own clock or assessment deadline has run out.
 *
 * The attempt pages already auto-submit while a student is looking at them.
 * This service covers the student who closes the tab, loses the connection or
 * simply never comes back: the scheduler calls it every minute and the same
 * grading paths used by the pages close the attempt from whatever answers
 * reached the server.
 *
 * An attempt is closed when either its own timer has expired (respecting
 * auto_submit_on_timeout) or the assessment's deadline has passed. The deadline
 * check matters for untimed assessments and for a student who started shortly
 * before the assessment closed: without it those attempts stay in_progress
 * forever. Per-student extensions are honoured through the model helper.
 *
 * Both passes only consider attempts that are still in_progress, and each
 * attempt is re-read under a row lock before it is finalized. A late browser
 * submission, a second scheduler run and overlapping workers therefore cannot
 * grade the same attempt twice.
 */
class AssessmentAutoSubmitService
{
    /**
     * Match the attempt pages' timeout grace. The page posts its saved answers
     * when the clock reaches zero, and that request can arrive a little late.
     * Without this wait the scheduler could close an attempt as empty seconds
     * before the student's own auto-submit landed.
     */
    public const AUTO_SUBMIT_GRACE_SECONDS = 120;

    public function __construct(
        private QuizService $quizService,
        private ExamService $examService,
    ) {}

    /**
     * @return array{quizzes: int, exams: int}
     */
    public function submitExpired(): array
    {
        return [
            'quizzes' => $this->submitExpiredQuizzes(),
            'exams' => $this->submitExpiredExams(),
        ];
    }

    private function submitExpiredQuizzes(): int
    {
        $closed = 0;

        QuizAttempt::query()
            ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
            ->whereNotNull('started_at')
            ->whereHas('quiz')
            ->with('quiz')
            ->chunkById(100, function (Collection $attempts) use (&$closed) {
                foreach ($attempts as $attempt) {
                    $quiz = $attempt->quiz;

                    if (! $quiz) {
                        continue;
                    }

                    $limit = (int) ($quiz->time_limit_minutes ?? 0);
                    $timerExpired = $limit > 0
                        && (bool) $quiz->auto_submit_on_timeout
                        && $this->pastGrace($attempt->started_at, $limit);
                    $deadlinePassed = $this->deadlinePassed(
                        $quiz->getEffectiveDeadlineForStudent((int) $attempt->student_id)
                    );

                    if (! $timerExpired && ! $deadlinePassed) {
                        continue;
                    }

                    if ($this->quizService->autoSubmitAttempt($attempt) !== null) {
                        $closed++;
                    }
                }
            });

        return $closed;
    }

    private function submitExpiredExams(): int
    {
        $closed = 0;

        ExamAttempt::query()
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->whereNotNull('started_at')
            ->whereHas('exam')
            ->with('exam')
            ->chunkById(100, function (Collection $attempts) use (&$closed) {
                foreach ($attempts as $attempt) {
                    $exam = $attempt->exam;

                    if (! $exam) {
                        continue;
                    }

                    $duration = (int) ($exam->duration_minutes ?? 0);
                    $timerExpired = $duration > 0
                        && (bool) $exam->auto_submit_on_timeout
                        && $this->pastGrace($attempt->started_at, $duration);
                    $deadlinePassed = $this->deadlinePassed(
                        $exam->getEffectiveDeadlineForStudent((int) $attempt->student_id)
                    );

                    if (! $timerExpired && ! $deadlinePassed) {
                        continue;
                    }

                    if ($this->autoSubmitExamAttempt($attempt) !== null) {
                        $closed++;
                    }
                }
            });

        return $closed;
    }

    /**
     * Lock the attempt, then reuse ExamService's grading and gradebook code.
     *
     * Public because the student's late browser request uses the same locked
     * path as the scheduler.
     */
    public function autoSubmitExamAttempt(ExamAttempt $attempt): ?ExamAttempt
    {
        return DB::transaction(function () use ($attempt) {
            $locked = ExamAttempt::query()
                ->with('exam')
                ->lockForUpdate()
                ->find($attempt->id);

            if (! $locked || $locked->status !== ExamAttempt::STATUS_IN_PROGRESS) {
                return null;
            }

            $answers = [];

            foreach ($locked->answers()->get() as $answer) {
                $answers[$answer->question_id] = $this->storedExamAnswer($answer);
            }

            return $this->examService->submitAttempt($locked, $answers);
        });
    }

    private function pastGrace(Carbon $startedAt, int $minutes): bool
    {
        return now()->gt(
            $startedAt->copy()
                ->addMinutes($minutes)
                ->addSeconds(self::AUTO_SUBMIT_GRACE_SECONDS)
        );
    }

    /**
     * The assessment deadline is a hard cutoff. The attempt timer gets a grace
     * window for the browser's own final POST; the assessment deadline does
     * not, because a student cannot start a new attempt after it anyway.
     */
    private function deadlinePassed(?Carbon $deadline): bool
    {
        return $deadline !== null && now()->gt($deadline);
    }

    /**
     * Rebuild the shape ExamService expects from what was stored on the row.
     */
    private function storedExamAnswer(ExamAnswer $answer): mixed
    {
        $data = $answer->answer_data;

        if (is_array($data) && $data !== []) {
            if (isset($data['choices']) && is_array($data['choices'])) {
                return $data['choices'];
            }

            if (array_key_exists('text', $data)) {
                return $data['text'];
            }

            if (array_key_exists('value', $data)) {
                return $data['value'];
            }

            return $data;
        }

        $stored = $answer->answer_text;

        if (is_string($stored)) {
            $trimmed = trim($stored);

            if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                $decoded = json_decode($trimmed, true);

                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return $stored;
    }
}
