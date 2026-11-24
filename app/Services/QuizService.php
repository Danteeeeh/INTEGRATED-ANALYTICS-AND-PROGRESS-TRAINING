<?php

namespace App\Services;

use App\Services\QuestionSnapshotService;

use App\Models\AuditLog;
use App\Models\Grade;
use App\Models\GradeHistory;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAnswerChoice;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    public function createQuiz(array $data): Quiz
    {
        return DB::transaction(function () use ($data) {
            $quiz = Quiz::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'lesson_id' => $data['lesson_id'] ?? null,
                'course_id' => $data['course_id'] ?? null,
                'class_id' => $data['class_id'] ?? null,
                'time_limit' => $data['time_limit'] ?? null, // in minutes
                'attempt_limit' => $data['attempt_limit'] ?? 1,
                'passing_score' => $data['passing_score'] ?? 70,
                'total_points' => $data['total_points'] ?? 100,
                'randomize_questions' => $data['randomize_questions'] ?? false,
                'randomize_choices' => $data['randomize_choices'] ?? false,
                'show_results_immediately' => $data['show_results_immediately'] ?? false,
                'allow_review' => $data['allow_review'] ?? true,
                'availability_from' => $data['availability_from'] ?? null,
                'availability_until' => $data['availability_until'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'created_by' => auth()->id(),
            ]);

            // Attach questions if provided
            if (isset($data['questions']) && is_array($data['questions'])) {
                foreach ($data['questions'] as $questionData) {
                    QuizQuestion::create([
                        'quiz_id' => $quiz->id,
                        'question_id' => $questionData['question_id'],
                        'points' => $questionData['points'] ?? 1,
                        'order' => $questionData['order'] ?? 0,
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'resource_type' => \App\Models\Quiz::class,
                'resource_id' => $quiz->id,
                'new_values' => ['title' => $quiz->title],
            ]);

            return $quiz;
        });
    }

    public function updateQuiz(Quiz $quiz, array $data): Quiz
    {
        return DB::transaction(function () use ($quiz, $data) {
            $oldValues = $quiz->toArray();

            $quiz->update([
                'title' => $data['title'] ?? $quiz->title,
                'description' => $data['description'] ?? $quiz->description,
                'instructions' => $data['instructions'] ?? $quiz->instructions,
                'lesson_id' => $data['lesson_id'] ?? $quiz->lesson_id,
                'course_id' => $data['course_id'] ?? $quiz->course_id,
                'class_id' => $data['class_id'] ?? $quiz->class_id,
                'time_limit' => $data['time_limit'] ?? $quiz->time_limit,
                'attempt_limit' => $data['attempt_limit'] ?? $quiz->attempt_limit,
                'passing_score' => $data['passing_score'] ?? $quiz->passing_score,
                'total_points' => $data['total_points'] ?? $quiz->total_points,
                'randomize_questions' => $data['randomize_questions'] ?? $quiz->randomize_questions,
                'randomize_choices' => $data['randomize_choices'] ?? $quiz->randomize_choices,
                'show_results_immediately' => $data['show_results_immediately'] ?? $quiz->show_results_immediately,
                'allow_review' => $data['allow_review'] ?? $quiz->allow_review,
                'availability_from' => $data['availability_from'] ?? $quiz->availability_from,
                'availability_until' => $data['availability_until'] ?? $quiz->availability_until,
                'status' => $data['status'] ?? $quiz->status,
            ]);

            // Update questions if provided
            if (isset($data['questions']) && is_array($data['questions'])) {
                // Remove existing questions not in the new list
                $existingQuestionIds = array_column($data['questions'], 'question_id');
                QuizQuestion::where('quiz_id', $quiz->id)
                    ->whereNotIn('question_id', $existingQuestionIds)
                    ->delete();

                // Add/update questions
                foreach ($data['questions'] as $questionData) {
                    QuizQuestion::updateOrCreate(
                        [
                            'quiz_id' => $quiz->id,
                            'question_id' => $questionData['question_id'],
                        ],
                        [
                            'points' => $questionData['points'] ?? 1,
                            'order' => $questionData['order'] ?? 0,
                        ]
                    );
                }
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'resource_type' => \App\Models\Quiz::class,
                'resource_id' => $quiz->id,
                'new_values' => [
                    'old_values' => $oldValues,
                    'new_values' => $quiz->toArray(),
                ],
            ]);

            return $quiz->fresh();
        });
    }

    public function startAttempt(Quiz $quiz): QuizAttempt
    {
        return DB::transaction(function () use ($quiz) {
            $studentId = auth()->id();

            // Check if quiz is available
            if ($quiz->availability_from && now()->lt($quiz->availability_from)) {
                throw ValidationException::withMessages([
                    'quiz' => 'This quiz is not yet available.',
                ]);
            }

            if ($quiz->availability_until && now()->gt($quiz->availability_until)) {
                throw ValidationException::withMessages([
                    'quiz' => 'This quiz is no longer available.',
                ]);
            }

            // Check attempt limit
            $attemptCount = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('student_id', $studentId)
                ->count();

            if ($attemptCount >= $quiz->attempt_limit) {
                throw ValidationException::withMessages([
                    'quiz' => 'You have reached the maximum number of attempts for this quiz.',
                ]);
            }

            // Check for existing in-progress attempt
            $existingAttempt = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('student_id', $studentId)
                ->where('status', 'in_progress')
                ->first();

            if ($existingAttempt) {
                return $existingAttempt;
            }

            // Get questions for the attempt
            $questions = $this->getQuizQuestions($quiz);

            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $studentId,
                'attempt_number' => $attemptCount + 1,
                'status' => 'in_progress',
                'started_at' => now(),
                'time_limit' => $quiz->time_limit,
            ]);

            // Create empty answer records for each question, each carrying a
            // frozen copy of the question as it was when the attempt started (§14).
            $snapshots = app(QuestionSnapshotService::class)->captureMany(
                $questions,
                $quiz->pointsByQuestionId()
            );

            foreach ($questions as $question) {
                QuizAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'question_snapshot' => $snapshots[$question->id] ?? null,
                ]);
            }

            AuditLog::create([
                'user_id' => $studentId,
                'action' => 'start',
                'resource_type' => \App\Models\QuizAttempt::class,
                'resource_id' => $attempt->id,
                'new_values' => [
                    'quiz_id' => $quiz->id,
                    'attempt_number' => $attempt->attempt_number,
                ],
            ]);

            return $attempt;
        });
    }

    protected function getQuizQuestions(Quiz $quiz): Collection
    {
        $query = QuizQuestion::where('quiz_id', $quiz->id)
            ->with('question.choices')
            ->orderBy('position');

        if ($quiz->shuffle_questions) {
            $query->inRandomOrder();
        }

        return $query->get()->pluck('question');
    }

    public function submitAttempt(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $answers) {
            if ($attempt->student_id !== auth()->id()) {
                throw ValidationException::withMessages([
                    'attempt' => 'You are not authorized to submit this attempt.',
                ]);
            }

            if ($attempt->status !== 'in_progress') {
                throw ValidationException::withMessages([
                    'attempt' => 'This attempt has already been submitted.',
                ]);
            }

            // Check time limit
            if ($attempt->time_limit && now()->diffInMinutes($attempt->started_at) > $attempt->time_limit) {
                $attempt->update([
                    'status' => 'auto_submitted',
                    'submitted_at' => now(),
                ]);
            } else {
                $attempt->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);
            }

            // Save answers
            foreach ($answers as $questionId => $answerData) {
                $quizAnswer = QuizAnswer::where('attempt_id', $attempt->id)
                    ->where('question_id', $questionId)
                    ->first();

                if ($quizAnswer) {
                    $quizAnswer->update([
                        'answer_text' => $answerData['text'] ?? null,
                        'is_correct' => $this->checkAnswer($quizAnswer->question, $answerData),
                    ]);

                    // Save answer choices for multiple choice questions
                    if (isset($answerData['choices']) && is_array($answerData['choices'])) {
                        QuizAnswerChoice::where('quiz_answer_id', $quizAnswer->id)->delete();
                        foreach ($answerData['choices'] as $choiceId) {
                            QuizAnswerChoice::create([
                                'quiz_answer_id' => $quizAnswer->id,
                                'question_choice_id' => $choiceId,
                            ]);
                        }
                    }
                }
            }

            // Calculate score
            $this->calculateAttemptScore($attempt);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'submit',
                'resource_type' => \App\Models\QuizAttempt::class,
                'resource_id' => $attempt->id,
                'new_values' => [
                    'quiz_id' => $attempt->quiz_id,
                    'score' => $attempt->score,
                ],
            ]);

            return $attempt->fresh();
        });
    }

    /**
     * Close an expired quiz attempt from the answers already on the server.
     *
     * Used by the browser-timeout path and by the scheduled command. The
     * attempt is re-read under a row lock so two workers (or a student's late
     * request and a worker) cannot grade and close the same attempt twice.
     *
     * Returns null when the attempt was already closed, which makes repeated
     * scheduler runs harmless.
     */
    public function autoSubmitAttempt(QuizAttempt $attempt): ?QuizAttempt
    {
        return DB::transaction(function () use ($attempt) {
            $locked = QuizAttempt::query()
                ->with('quiz')
                ->lockForUpdate()
                ->find($attempt->id);

            if (! $locked || $locked->status !== QuizAttempt::STATUS_IN_PROGRESS) {
                return null;
            }

            $quiz = $locked->quiz;

            if (! $quiz) {
                return null;
            }

            $locked->loadMissing('answers.question', 'answers.selectedChoices');

            $totalScore = 0.0;
            $totalPoints = 0.0;
            $pointsByQuestion = $quiz->pointsByQuestionId();
            $grader = app(QuestionGrader::class);

            foreach ($locked->answers as $quizAnswer) {
                $question = $quizAnswer->question;
                $snapshot = $quizAnswer->snapshotArray();
                $submitted = $this->decodeStoredAnswer($quizAnswer->answer_text);

                $pivotPoints = $pointsByQuestion[$quizAnswer->question_id]
                    ?? $snapshot['points']
                    ?? $question?->default_points
                    ?? 1;

                $result = $grader->grade($submitted, $snapshot, $question, (float) $pivotPoints);

                if ($result['manual']) {
                    // Essays and unkeyed answers wait for the instructor. They
                    // are excluded from the denominator so an unmarked answer
                    // is not reported as a confident zero.
                    $quizAnswer->update([
                        'is_correct' => null,
                        'points_awarded' => null,
                    ]);

                    continue;
                }

                $quizAnswer->update([
                    'is_correct' => $result['is_correct'],
                    'points_awarded' => $result['points'],
                ]);

                $this->syncSelectedChoices($quizAnswer, $submitted, $question);

                $totalScore += $result['points'];
                $totalPoints += $pivotPoints;
            }

            $scorePercent = $totalPoints > 0 ? ($totalScore / $totalPoints) * 100 : 0;
            $hasPendingGrades = $this->hasPendingGrades($locked);
            $isPassed = $totalPoints > 0 && ! $hasPendingGrades
                ? $scorePercent >= $quiz->passing_score_percent
                : null;
            $limitMinutes = (int) ($quiz->time_limit_minutes ?? 0);
            $timerDeadline = $limitMinutes > 0 && $locked->started_at
                ? $locked->started_at->copy()->addMinutes($limitMinutes)
                : null;

            // Normally the attempt ends at its own timer. When the scheduler
            // closes it because the assessment deadline passed first, the timer
            // may still be in the future, so end it now and charge only the
            // time actually spent.
            $endedAt = $timerDeadline && $timerDeadline->isPast()
                ? $timerDeadline
                : now();

            $timeSpent = $locked->started_at
                ? max(0, (int) $locked->started_at->diffInSeconds($endedAt))
                : 0;

            $locked->update([
                'ended_at' => $endedAt,
                'submitted_at' => now(),
                'time_spent_seconds' => $timeSpent,
                'score' => $totalScore,
                'score_percent' => round($scorePercent, 2),
                'is_passed' => $isPassed,
                'status' => QuizAttempt::STATUS_AUTO_SUBMITTED,
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'auto_submit',
                'resource_type' => QuizAttempt::class,
                'resource_id' => $locked->id,
                'new_values' => [
                    'quiz_id' => $locked->quiz_id,
                    'score_percent' => $locked->score_percent,
                    'reason' => $timerDeadline && $timerDeadline->isPast()
                        ? 'time_expired'
                        : 'deadline_passed',
                ],
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Stored answers are scalars for single-answer questions and JSON lists for
     * multiple-answer ones. The grader wants the original shape back.
     */
    private function decodeStoredAnswer(mixed $stored): mixed
    {
        if (! is_string($stored)) {
            return $stored;
        }

        $trimmed = trim($stored);

        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
            $decoded = json_decode($trimmed, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $stored;
    }

    /**
     * Persist the student's chosen choices to the quiz_answer_choices pivot.
     *
     * @param  mixed  $answerData  Stored scalar, list of choice IDs, or null
     */
    private function syncSelectedChoices(QuizAnswer $quizAnswer, mixed $answerData, ?Question $question): void
    {
        $snapshot = $quizAnswer->snapshotArray();
        $qtype = $question?->question_type ?? $snapshot['question_type'] ?? null;

        if (! $qtype || ! in_array($qtype, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_MULTIPLE_ANSWER, Question::TYPE_TRUE_FALSE], true)) {
            return;
        }

        $selected = [];

        if (is_array($answerData)) {
            foreach ($answerData as $choiceId) {
                if (is_numeric($choiceId)) {
                    $selected[] = (int) $choiceId;
                }
            }
        } elseif (is_numeric($answerData)) {
            $selected[] = (int) $answerData;
        }

        if ($selected !== []) {
            $quizAnswer->selectedChoices()->sync($selected);
        }
    }

    /**
     * Has the instructor still got work to do on this attempt?
     */
    private function hasPendingGrades(QuizAttempt $attempt): bool
    {
        return $attempt->answers()
            ->whereNull('is_correct')
            ->whereNotNull('answer_text')
            ->where('answer_text', '!=', '')
            ->exists();
    }

    protected function checkAnswer(Question $question, array $answerData): bool
    {
        switch ($question->type) {
            case 'multiple_choice':
            case 'true_false':
                if (isset($answerData['choices']) && is_array($answerData['choices'])) {
                    $correctChoices = $question->choices()->where('is_correct', true)->pluck('id')->toArray();

                    return count($answerData['choices']) === count($correctChoices) &&
                           empty(array_diff($answerData['choices'], $correctChoices));
                }

                return false;

            case 'multiple_answer':
                if (isset($answerData['choices']) && is_array($answerData['choices'])) {
                    $correctChoices = $question->choices()->where('is_correct', true)->pluck('id')->toArray();
                    $selectedChoices = $answerData['choices'];
                    $correctSelected = array_intersect($selectedChoices, $correctChoices);

                    return count($correctSelected) === count($correctChoices);
                }

                return false;

            case 'short_answer':
            case 'identification':
                return isset($answerData['text']) &&
                       strcasecmp(trim($answerData['text']), trim($question->correct_answer)) === 0;

            case 'essay':
                // Essays require manual grading
                return null;

            default:
                return false;
        }
    }

    protected function calculateAttemptScore(QuizAttempt $attempt): void
    {
        $quizAnswers = QuizAnswer::where('attempt_id', $attempt->id)->get();
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($quizAnswers as $quizAnswer) {
            $quizQuestion = QuizQuestion::where('quiz_id', $attempt->quiz_id)
                ->where('question_id', $quizAnswer->question_id)
                ->first();

            if ($quizQuestion) {
                $totalPoints += $quizQuestion->points;
                if ($quizAnswer->is_correct === true) {
                    $earnedPoints += $quizQuestion->points;
                }
            }
        }

        $score = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
        $passed = $score >= $attempt->quiz->passing_score;

        $attempt->update([
            'score' => $score,
            'total_points' => $totalPoints,
            'earned_points' => $earnedPoints,
            'passed' => $passed,
            'graded_at' => now(),
        ]);

        // Create or update grade item
        $gradeItem = GradeItem::firstOrCreate([
            'related_type' => Quiz::class,
            'related_id' => $attempt->quiz_id,
        ], [
            'class_id' => $attempt->quiz->class_id,
            'title' => $attempt->quiz->title,
            'max_points' => $totalPoints,
            'item_type' => GradeItem::TYPE_QUIZ,
            'is_released' => true,
            'released_at' => now(),
        ]);

        // Create grade record
        Grade::updateOrCreate(
            [
                'grade_item_id' => $gradeItem->id,
                'student_id' => $attempt->student_id,
            ],
            [
                'points' => $earnedPoints,
                'score_percent' => $score,
                'graded_by' => null, // Auto-graded
                'graded_at' => now(),
            ]
        );
    }

    public function gradeAttempt(QuizAttempt $attempt, array $gradingData): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $gradingData) {
            $oldScore = $attempt->score;

            // Update specific answers with manual grading
            if (isset($gradingData['answers']) && is_array($gradingData['answers'])) {
                foreach ($gradingData['answers'] as $questionId => $answerGrading) {
                    $quizAnswer = QuizAnswer::where('attempt_id', $attempt->id)
                        ->where('question_id', $questionId)
                        ->first();

                    if ($quizAnswer) {
                        $quizAnswer->update([
                            'is_correct' => $answerGrading['is_correct'] ?? $quizAnswer->is_correct,
                            'feedback' => $answerGrading['feedback'] ?? null,
                            'points_awarded' => $answerGrading['points_awarded'] ?? null,
                        ]);
                    }
                }
            }

            // Recalculate score
            $this->calculateAttemptScore($attempt);

            // Update grade history if score changed
            if ($oldScore !== $attempt->score) {
                GradeHistory::create([
                    'student_id' => $attempt->student_id,
                    'gradable_type' => Quiz::class,
                    'gradable_id' => $attempt->quiz_id,
                    'previous_grade' => $oldScore,
                    'new_grade' => $attempt->score,
                    'modified_by' => auth()->id(),
                    'reason' => 'Manual quiz grading adjustment',
                ]);
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'grade',
                'resource_type' => \App\Models\QuizAttempt::class,
                'resource_id' => $attempt->id,
                'new_values' => [
                    'previous_score' => $oldScore,
                    'new_score' => $attempt->score,
                ],
            ]);

            return $attempt->fresh();
        });
    }

    public function deleteQuiz(Quiz $quiz): bool
    {
        return DB::transaction(function () use ($quiz) {
            $quiz->delete();

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'resource_type' => \App\Models\Quiz::class,
                'resource_id' => $quiz->id,
                'new_values' => ['title' => $quiz->title],
            ]);

            return true;
        });
    }
}
