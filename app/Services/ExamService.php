<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAnswer;
use App\Models\ExamAnswerChoice;
use App\Models\ExamQuestion;
use App\Models\Grade;
use App\Models\GradeHistory;
use App\Models\Question;
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
                'resource' => 'exam',
                'resource_id' => $exam->id,
                'details' => ['title' => $exam->title, 'exam_type' => $exam->exam_type],
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
                'resource' => 'exam',
                'resource_id' => $exam->id,
                'details' => [
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

            // Create empty answer records for each question
            foreach ($questions as $question) {
                ExamAnswer::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                ]);
            }

            AuditLog::create([
                'user_id' => $studentId,
                'action' => 'start',
                'resource' => 'exam_attempt',
                'resource_id' => $attempt->id,
                'details' => [
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
            ->with('question.choices')
            ->orderBy('order');

        if ($exam->shuffle_questions) {
            $query->inRandomOrder();
        }

        return $query->get()->pluck('question');
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
            foreach ($answers as $questionId => $answerData) {
                $examAnswer = ExamAnswer::where('exam_attempt_id', $attempt->id)
                    ->where('question_id', $questionId)
                    ->first();

                if ($examAnswer) {
                    $examAnswer->update([
                        'answer_text' => $answerData['text'] ?? null,
                        'answer_data' => $answerData['data'] ?? null,
                        'is_correct' => $this->checkAnswer($examAnswer->question, $answerData),
                        'answered_at' => now(),
                    ]);

                    // Save answer choices for multiple choice questions
                    if (isset($answerData['choices']) && is_array($answerData['choices'])) {
                        ExamAnswerChoice::where('exam_answer_id', $examAnswer->id)->delete();
                        foreach ($answerData['choices'] as $choiceId) {
                            ExamAnswerChoice::create([
                                'exam_answer_id' => $examAnswer->id,
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
                'resource' => 'exam_attempt',
                'resource_id' => $attempt->id,
                'details' => [
                    'exam_id' => $attempt->exam_id,
                    'score' => $attempt->score_percent,
                ],
            ]);

            return $attempt->fresh();
        });
    }

    protected function checkAnswer(Question $question, array $answerData): bool
    {
        switch ($question->question_type) {
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

    protected function calculateAttemptScore(ExamAttempt $attempt): void
    {
        $examAnswers = ExamAnswer::where('exam_attempt_id', $attempt->id)->get();
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($examAnswers as $examAnswer) {
            $examQuestion = ExamQuestion::where('exam_id', $attempt->exam_id)
                ->where('question_id', $examAnswer->question_id)
                ->first();

            if ($examQuestion) {
                $totalPoints += $examQuestion->points;
                if ($examAnswer->is_correct === true) {
                    $earnedPoints += $examQuestion->points;
                }
            }
        }

        $score = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
        $passed = $score >= $attempt->exam->passing_score_percent;

        $attempt->update([
            'score' => $earnedPoints,
            'total_points' => $totalPoints,
            'earned_points' => $earnedPoints,
            'score_percent' => $score,
            'is_passed' => $passed,
            'graded_at' => now(),
        ]);

        // Create grade record if exam is auto-graded
        if ($attempt->exam->result_visibility === 'immediately') {
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

            // Update specific answers with manual grading
            if (isset($gradingData['answers']) && is_array($gradingData['answers'])) {
                foreach ($gradingData['answers'] as $questionId => $answerGrading) {
                    $examAnswer = ExamAnswer::where('exam_attempt_id', $attempt->id)
                        ->where('question_id', $questionId)
                        ->first();

                    if ($examAnswer) {
                        $examAnswer->update([
                            'is_correct' => $answerGrading['is_correct'] ?? $examAnswer->is_correct,
                            'points_awarded' => $answerGrading['points_awarded'] ?? null,
                            'feedback' => $answerGrading['feedback'] ?? null,
                            'grader_notes' => $answerGrading['grader_notes'] ?? null,
                        ]);
                    }
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
                'resource' => 'exam_attempt',
                'resource_id' => $attempt->id,
                'details' => [
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
                'resource' => 'exam',
                'resource_id' => $exam->id,
                'details' => ['title' => $exam->title],
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
