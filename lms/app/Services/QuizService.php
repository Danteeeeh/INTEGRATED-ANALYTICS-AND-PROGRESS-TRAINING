<?php

namespace App\Services;

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
                'resource' => 'quiz',
                'resource_id' => $quiz->id,
                'details' => ['title' => $quiz->title],
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
                'resource' => 'quiz',
                'resource_id' => $quiz->id,
                'details' => [
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

            // Create empty answer records for each question
            foreach ($questions as $question) {
                QuizAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                ]);
            }

            AuditLog::create([
                'user_id' => $studentId,
                'action' => 'start',
                'resource' => 'quiz_attempt',
                'resource_id' => $attempt->id,
                'details' => [
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
            ->orderBy('order');

        if ($quiz->randomize_questions) {
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
                'resource' => 'quiz_attempt',
                'resource_id' => $attempt->id,
                'details' => [
                    'quiz_id' => $attempt->quiz_id,
                    'score' => $attempt->score,
                ],
            ]);

            return $attempt->fresh();
        });
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

        // Create grade record
        Grade::updateOrCreate(
            [
                'student_id' => $attempt->student_id,
                'gradable_type' => Quiz::class,
                'gradable_id' => $attempt->quiz_id,
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
                'resource' => 'quiz_attempt',
                'resource_id' => $attempt->id,
                'details' => [
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
                'resource' => 'quiz',
                'resource_id' => $quiz->id,
                'details' => ['title' => $quiz->title],
            ]);

            return true;
        });
    }
}
