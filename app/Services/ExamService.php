<?php

namespace App\Services;

use App\Services\QuestionGrader;
use App\Services\QuestionSnapshotService;

use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAnswer;
use App\Models\ExamAnswerChoice;
use App\Models\ExamQuestion;
use App\Models\Grade;
use App\Models\GradeHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamService
{
    public function createExam(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            $exam = Exam::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'exam_type' => $data['exam_type'] ?? 'module',
                'class_id' => $data['class_id'],
                'course_id' => $data['course_id'] ?? null,
                'module_id' => $data['module_id'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? 60,
                'total_points' => $data['total_points'] ?? 100,
                'passing_score_percent' => $data['passing_score_percent'] ?? 60,
                'grade_weight' => $data['grade_weight'] ?? 30,
                'attempt_limit' => $data['attempt_limit'] ?? 1,
                'allow_review' => $data['allow_review'] ?? false,
                'requires_proctoring' => $data['requires_proctoring'] ?? false,
                'proctoring_method' => $data['proctoring_method'] ?? null,
                'proctoring_instructions' => $data['proctoring_instructions'] ?? null,
                'record_session' => $data['record_session'] ?? false,
                'detect_tab_switch' => $data['detect_tab_switch'] ?? false,
                'detect_copy_paste' => $data['detect_copy_paste'] ?? false,
                'allow_navigation' => $data['allow_navigation'] ?? false,
                'shuffle_questions' => $data['shuffle_questions'] ?? true,
                'shuffle_choices' => $data['shuffle_choices'] ?? true,
                'show_question_number' => $data['show_question_number'] ?? true,
                'show_timer' => $data['show_timer'] ?? true,
                'auto_save_seconds' => $data['auto_save_seconds'] ?? 30,
                'auto_submit_on_timeout' => $data['auto_submit_on_timeout'] ?? true,
                'result_visibility' => $data['result_visibility'] ?? 'after_grading',
                'show_correct_answers' => $data['show_correct_answers'] ?? false,
                'show_score' => $data['show_score'] ?? false,
                'results_release_date' => $data['results_release_date'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'require_confirmation' => $data['require_confirmation'] ?? true,
                'status' => $data['status'] ?? 'draft',
                'created_by' => auth()->id(),
                'slug' => \Illuminate\Support\Str::slug($data['title']) . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8)),
            ]);

            // Attach questions if provided
            if (isset($data['questions']) && is_array($data['questions'])) {
                foreach ($data['questions'] as $questionData) {
                    ExamQuestion::create([
                        'exam_id' => $exam->id,
                        'question_id' => $questionData['question_id'],
                        'points' => $questionData['points'] ?? 1,
                        'order' => $questionData['order'] ?? 0,
                        'is_required' => $questionData['is_required'] ?? true,
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'resource_type' => \App\Models\Exam::class,
                'resource_id' => $exam->id,
                'new_values' => ['title' => $exam->title, 'exam_type' => $exam->exam_type],
            ]);

            return $exam;
        });
    }

    public function updateExam(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data) {
            $oldValues = $exam->toArray();

            $exam->update([
                'title' => $data['title'] ?? $exam->title,
                'description' => $data['description'] ?? $exam->description,
                'instructions' => $data['instructions'] ?? $exam->instructions,
                'exam_type' => $data['exam_type'] ?? $exam->exam_type,
                'class_id' => $data['class_id'] ?? $exam->class_id,
                'course_id' => $data['course_id'] ?? $exam->course_id,
                'module_id' => $data['module_id'] ?? $exam->module_id,
                'duration_minutes' => $data['duration_minutes'] ?? $exam->duration_minutes,
                'total_points' => $data['total_points'] ?? $exam->total_points,
                'passing_score_percent' => $data['passing_score_percent'] ?? $exam->passing_score_percent,
                'grade_weight' => $data['grade_weight'] ?? $exam->grade_weight,
                'attempt_limit' => $data['attempt_limit'] ?? $exam->attempt_limit,
                'allow_review' => $data['allow_review'] ?? $exam->allow_review,
                'requires_proctoring' => $data['requires_proctoring'] ?? $exam->requires_proctoring,
                'proctoring_method' => $data['proctoring_method'] ?? $exam->proctoring_method,
                'proctoring_instructions' => $data['proctoring_instructions'] ?? $exam->proctoring_instructions,
                'record_session' => $data['record_session'] ?? $exam->record_session,
                'detect_tab_switch' => $data['detect_tab_switch'] ?? $exam->detect_tab_switch,
                'detect_copy_paste' => $data['detect_copy_paste'] ?? $exam->detect_copy_paste,
                'allow_navigation' => $data['allow_navigation'] ?? $exam->allow_navigation,
                'shuffle_questions' => $data['shuffle_questions'] ?? $exam->shuffle_questions,
                'shuffle_choices' => $data['shuffle_choices'] ?? $exam->shuffle_choices,
                'show_question_number' => $data['show_question_number'] ?? $exam->show_question_number,
                'show_timer' => $data['show_timer'] ?? $exam->show_timer,
                'auto_save_seconds' => $data['auto_save_seconds'] ?? $exam->auto_save_seconds,
                'auto_submit_on_timeout' => $data['auto_submit_on_timeout'] ?? $exam->auto_submit_on_timeout,
                'result_visibility' => $data['result_visibility'] ?? $exam->result_visibility,
                'show_correct_answers' => $data['show_correct_answers'] ?? $exam->show_correct_answers,
                'show_score' => $data['show_score'] ?? $exam->show_score,
                'results_release_date' => $data['results_release_date'] ?? $exam->results_release_date,
                'starts_at' => $data['starts_at'] ?? $exam->starts_at,
                'ends_at' => $data['ends_at'] ?? $exam->ends_at,
                'require_confirmation' => $data['require_confirmation'] ?? $exam->require_confirmation,
                'status' => $data['status'] ?? $exam->status,
            ]);

            // Update questions if provided
            if (isset($data['questions']) && is_array($data['questions'])) {
                $existingQuestionIds = array_column($data['questions'], 'question_id');
                ExamQuestion::where('exam_id', $exam->id)
                    ->whereNotIn('question_id', $existingQuestionIds)
                    ->delete();

                foreach ($data['questions'] as $questionData) {
                    ExamQuestion::updateOrCreate(
                        [
                            'exam_id' => $exam->id,
                            'question_id' => $questionData['question_id'],
                        ],
                        [
                            'points' => $questionData['points'] ?? 1,
                            'order' => $questionData['order'] ?? 0,
                            'is_required' => $questionData['is_required'] ?? true,
                        ]
                    );
                }
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'resource_type' => \App\Models\Exam::class,
                'resource_id' => $exam->id,
                'new_values' => [
                    'old_values' => $oldValues,
                    'new_values' => $exam->toArray(),
                ],
            ]);

            return $exam->fresh();
        });
    }

    public function startAttempt(Exam $exam): ExamAttempt
    {
        return DB::transaction(function () use ($exam) {
            $studentId = auth()->id();

            // Check if exam is available
            if (!$exam->isAvailable()) {
                throw ValidationException::withMessages([
                    'exam' => 'This exam is not currently available.',
                ]);
            }

            // Check attempt limit
            $attemptCount = ExamAttempt::ofExam($exam->id)
                ->ofStudent($studentId)
                ->count();

            if ($exam->attempt_limit && $attemptCount >= $exam->attempt_limit) {
                throw ValidationException::withMessages([
                    'exam' => 'You have reached the maximum number of attempts for this exam.',
                ]);
            }

            // Check for existing in-progress attempt
            $existingAttempt = ExamAttempt::ofExam($exam->id)
                ->ofStudent($studentId)
                ->inProgress()
                ->first();

            if ($existingAttempt) {
                return $existingAttempt;
            }

            // Get questions for the attempt
            $questions = $this->getExamQuestions($exam);

            $attempt = ExamAttempt::create([
                'exam_id' => $exam->id,
                'student_id' => $studentId,
                'attempt_number' => $attemptCount + 1,
                'status' => ExamAttempt::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'confirmed_before_start' => false,
            ]);

            // Create empty answer records for each question, each carrying a
            // frozen copy of the question as it was when the attempt started (§14).
            $snapshots = app(QuestionSnapshotService::class)->captureMany(
                $questions,
                $exam->pointsByQuestionId()
            );

            foreach ($questions as $question) {
                ExamAnswer::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'question_snapshot' => $snapshots[$question->id] ?? null,
                ]);
            }

            AuditLog::create([
                'user_id' => $studentId,
                'action' => 'start',
                'resource_type' => \App\Models\ExamAttempt::class,
                'resource_id' => $attempt->id,
                'new_values' => [
                    'exam_id' => $exam->id,
                    'attempt_number' => $attempt->attempt_number,
                ],
            ]);

            return $attempt;
        });
    }

    protected function getExamQuestions(Exam $exam): Collection
    {
        $query = ExamQuestion::where('exam_id', $exam->id)
            // Retired (archived) questions are no longer served for new exams;
            // past attempts keep their snapshot (§3, §14).
            ->whereHas('question', fn ($q) => $q->availableForAttempts())
            ->with('question.choices')
            ->orderBy('order');

        if ($exam->shuffle_questions) {
            $query->inRandomOrder();
        }

        return $query->get()->pluck('question')->filter()->values();
    }

    public function confirmStart(ExamAttempt $attempt): ExamAttempt
    {
        $attempt->update([
            'confirmed_before_start' => true,
            'confirmed_at' => now(),
        ]);

        return $attempt->fresh();
    }

    public function submitAttempt(ExamAttempt $attempt, array $answers): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $answers) {
            if ($attempt->student_id !== auth()->id()) {
                throw ValidationException::withMessages([
                    'attempt' => 'You are not authorized to submit this attempt.',
                ]);
            }

            if ($attempt->status !== ExamAttempt::STATUS_IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'attempt' => 'This attempt has already been submitted.',
                ]);
            }

            // Check time limit
            if ($attempt->exam->duration_minutes && now()->diffInMinutes($attempt->started_at) > $attempt->exam->duration_minutes) {
                $attempt->update([
                    'status' => ExamAttempt::STATUS_AUTO_SUBMITTED,
                    'ended_at' => now(),
                    'submitted_at' => now(),
                ]);
            } else {
                $attempt->update([
                    'status' => ExamAttempt::STATUS_SUBMITTED,
                    'ended_at' => now(),
                    'submitted_at' => now(),
                ]);
            }

            // Save answers
            $grader = app(QuestionGrader::class);
            $pointsByQuestion = $attempt->exam->pointsByQuestionId();

            foreach ($answers as $questionId => $answerData) {
                $examAnswer = ExamAnswer::where('exam_attempt_id', $attempt->id)
                    ->where('question_id', $questionId)
                    ->first();

                if (! $examAnswer) {
                    continue;
                }

                // The exam form posts a scalar id for one-answer questions, an
                // array of ids for multiple answer, and a plain string for text.
                $submitted = $this->normalizeSubmitted($answerData);

                // Grade against the frozen question (§14). This also fixes
                // identification and short answer, which used to read a
                // `correct_answer` column that does not exist.
                $result = $grader->grade(
                    $submitted,
                    $examAnswer->snapshotArray(),
                    $examAnswer->question,
                    $pointsByQuestion[$questionId] ?? null
                );

                $examAnswer->answer_text = is_array($submitted)
                    ? json_encode(array_values($submitted))
                    : $submitted;

                if ($result['manual']) {
                    // Essay, or an identification question with no answer key
                    // configured: the instructor decides (§21).
                    $examAnswer->is_correct = null;
                    $examAnswer->points_awarded = null;
                    $examAnswer->answer_data = $examAnswer->answer_data;
                } else {
                    $examAnswer->is_correct = $result['is_correct'];
                    $examAnswer->points_awarded = $result['points'];
                }

                $examAnswer->answered_at = now();
                $examAnswer->save();

                $this->syncChoiceSelections($examAnswer, $submitted);
            }

            // Calculate score
            $this->calculateAttemptScore($attempt);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'submit',
                'resource_type' => \App\Models\ExamAttempt::class,
                'resource_id' => $attempt->id,
                'new_values' => [
                    'exam_id' => $attempt->exam_id,
                    'score' => $attempt->score_percent,
                ],
            ]);

            return $attempt->fresh();
        });
    }

    /**
     * Normalises whatever the request carried into one shape per question type.
     *
     * @param  mixed  $answerData
     * @return array<int>|string|null
     */
    protected function normalizeSubmitted(mixed $answerData): array|string|null
    {
        if (is_array($answerData)) {
            // Accepts both the flat array the form posts and the older
            // ['text' => ..., 'choices' => [...]] envelope.
            if (array_key_exists('choices', $answerData) || array_key_exists('text', $answerData)) {
                $choiceIds = $answerData['choices'] ?? null;
                $text = $answerData['text'] ?? null;

                if (is_array($choiceIds) && $choiceIds !== []) {
                    return array_values(array_map('intval', $choiceIds));
                }

                return $text === null ? null : (string) $text;
            }

            return array_values($answerData);
        }

        if ($answerData === null) {
            return null;
        }

        if (is_bool($answerData)) {
            return $answerData ? '1' : '0';
        }

        return (string) $answerData;
    }

    /**
     * Mirrors the selected choices into exam_answer_choices so review screens
     * keep working whether the selection arrived as one id or many.
     *
     * @param  array<int>|string|null  $submitted
     */
    protected function syncChoiceSelections(ExamAnswer $examAnswer, array|string|null $submitted): void
    {
        $selected = is_array($submitted)
            ? $submitted
            : (is_numeric($submitted) ? [(int) $submitted] : []);

        ExamAnswerChoice::where('exam_answer_id', $examAnswer->id)->delete();

        foreach ($selected as $choiceId) {
            ExamAnswerChoice::create([
                'exam_answer_id' => $examAnswer->id,
                'question_choice_id' => (int) $choiceId,
            ]);
        }
    }

    /**
     * Sums the attempt from the stored per-answer results.
     *
     * Questions still awaiting an instructor (essays, identification with no
     * answer key) are left out of the denominator: counting them as zero would
     * mark a student down for work nobody has marked yet.
     */
    protected function calculateAttemptScore(ExamAttempt $attempt): void
    {
        $examAnswers = ExamAnswer::where('exam_attempt_id', $attempt->id)->get();

        $pointsByQuestion = $attempt->exam->pointsByQuestionId();
        $totalPoints = 0.0;
        $earnedPoints = 0.0;
        $pending = false;

        foreach ($examAnswers as $examAnswer) {
            $answered = $examAnswer->answer_text !== null
                && trim((string) $examAnswer->answer_text) !== '';

            if ($examAnswer->is_correct === null && $answered) {
                // Submitted, but nobody has judged it yet (§21). Kept out of the
                // denominator so unmarked work is not scored as wrong.
                $pending = true;

                continue;
            }

            // Not attempted at all, or already marked: counts either way, at the
            // points this exam actually assigns.
            $points = $pointsByQuestion[$examAnswer->question_id]
                ?? $examAnswer->snapshotArray()['points']
                ?? $examAnswer->question?->default_points
                ?? 1;

            $totalPoints += (float) $points;
            $earnedPoints += (float) ($examAnswer->points_awarded ?? 0);
        }

        $score = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;

        // Only a definitive pass/fail once everything is marked.
        $passed = (! $pending && $totalPoints > 0)
            ? $score >= $attempt->exam->passing_score_percent
            : null;

        $attempt->update([
            'score' => $earnedPoints,
            'total_points' => $totalPoints,
            'earned_points' => $earnedPoints,
            'score_percent' => round($score, 2),
            'is_passed' => $passed,
            'graded_at' => $pending ? null : now(),
        ]);

        // Create grade record if exam is auto-graded
        if (! $pending && $attempt->exam->result_visibility === 'immediately') {
            Grade::updateOrCreate(
                [
                    'student_id' => $attempt->student_id,
                    'gradable_type' => Exam::class,
                    'gradable_id' => $attempt->exam_id,
                ],
                [
                    'points' => $earnedPoints,
                    'max_points' => $totalPoints,
                    'percentage' => $score,
                    'graded_by' => null, // Auto-graded
                    'graded_at' => now(),
                ]
            );
        }
    }

    public function gradeAttempt(ExamAttempt $attempt, array $gradingData): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $gradingData) {
            $oldScore = $attempt->score_percent;
            $pointsByQuestion = $attempt->exam->pointsByQuestionId();

            // Update specific answers with manual grading
            if (isset($gradingData['answers']) && is_array($gradingData['answers'])) {
                foreach ($gradingData['answers'] as $questionId => $answerGrading) {
                    $examAnswer = ExamAnswer::where('exam_attempt_id', $attempt->id)
                        ->where('question_id', $questionId)
                        ->first();

                    if (! $examAnswer) {
                        continue;
                    }

                    $update = [
                        'feedback' => $answerGrading['feedback'] ?? $examAnswer->feedback,
                        'grader_notes' => $answerGrading['grader_notes'] ?? $examAnswer->grader_notes,
                    ];

                    $verdictGiven = array_key_exists('is_correct', $answerGrading);

                    if ($verdictGiven) {
                        $update['is_correct'] = $answerGrading['is_correct'];
                    }

                    if (array_key_exists('points_awarded', $answerGrading)) {
                        $update['points_awarded'] = $answerGrading['points_awarded'];
                    } elseif ($verdictGiven && $answerGrading['is_correct'] !== null) {
                        // The form sometimes posts only the right/wrong tick.
                        // Derive the mark so the answer does not linger as
                        // "awaiting grading" after the instructor has judged it.
                        $max = $pointsByQuestion[$examAnswer->question_id]
                            ?? $examAnswer->snapshotArray()['points']
                            ?? $examAnswer->question?->default_points
                            ?? 1;

                        $update['points_awarded'] = $answerGrading['is_correct'] ? (float) $max : 0.0;
                    }

                    $examAnswer->update($update);
                }
            }

            // Recalculate score
            $this->calculateAttemptScore($attempt);

            // Update grade history if score changed
            if ($oldScore !== $attempt->score_percent) {
                GradeHistory::create([
                    'student_id' => $attempt->student_id,
                    'gradable_type' => Exam::class,
                    'gradable_id' => $attempt->exam_id,
                    'previous_grade' => $oldScore,
                    'new_grade' => $attempt->score_percent,
                    'modified_by' => auth()->id(),
                    'reason' => 'Manual exam grading adjustment',
                ]);
            }

            // Update attempt status
            $attempt->update([
                'status' => ExamAttempt::STATUS_GRADED,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'grade',
                'resource_type' => \App\Models\ExamAttempt::class,
                'resource_id' => $attempt->id,
                'new_values' => [
                    'previous_score' => $oldScore,
                    'new_score' => $attempt->score_percent,
                ],
            ]);

            return $attempt->fresh();
        });
    }

    public function deleteExam(Exam $exam): bool
    {
        return DB::transaction(function () use ($exam) {
            $exam->delete();

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'resource_type' => \App\Models\Exam::class,
                'resource_id' => $exam->id,
                'new_values' => ['title' => $exam->title],
            ]);

            return true;
        });
    }

    public function publishExam(Exam $exam): Exam
    {
        $exam->update([
            'status' => Exam::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        return $exam->fresh();
    }

    public function closeExam(Exam $exam): Exam
    {
        $exam->update([
            'status' => Exam::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        return $exam->fresh();
    }

    public function recordProctoringEvent(ExamAttempt $attempt, string $eventType, array $data = []): void
    {
        $log = $attempt->proctoring_log ?? [];
        $log[] = [
            'event' => $eventType,
            'timestamp' => now()->toISOString(),
            'data' => $data,
        ];

        $attempt->update([
            'proctoring_log' => $log,
        ]);

        // Update counters based on event type
        match($eventType) {
            'tab_switch' => $attempt->increment('tab_switch_count'),
            'suspicious_activity' => $attempt->increment('suspicious_activity_count'),
            default => null,
        };

        // Auto-flag if thresholds exceeded
        if ($attempt->tab_switch_count > 5 || $attempt->suspicious_activity_count > 3) {
            $attempt->update(['flagged_for_review' => true]);
        }
    }
}
