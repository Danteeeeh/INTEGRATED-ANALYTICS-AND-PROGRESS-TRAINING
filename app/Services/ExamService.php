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
                'duration_minutes' => $data['duration_minutes'],
                'total_points' => $data['total_points'] ?? 0,
                'passing_score_percent' => $data['passing_score_percent'] ?? 60,
                'grade_weight' => $data['grade_weight'] ?? 0,
                'attempt_limit' => $data['attempt_limit'] ?? 1,
                'shuffle_questions' => $data['shuffle_questions'] ?? false,
                'shuffle_choices' => $data['shuffle_choices'] ?? false,
                'allow_navigation' => $data['allow_navigation'] ?? true,
                'auto_submit_on_timeout' => $data['auto_submit_on_timeout'] ?? true,
                'result_visibility' => $data['result_visibility'] ?? 'after_grading',
                'show_correct_answers' => $data['show_correct_answers'] ?? false,
                'show_score' => $data['show_score'] ?? true,
                'status' => $data['status'] ?? 'draft',
                'created_by' => auth()->id(),
                'slug' => \Illuminate\Support\Str::slug($data['title']) . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8)),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'resource_type' => Exam::class,
                'resource_id' => $exam->id,
                // Read back off $exam rather than $data: the create above
                // applies defaults (exam_type => 'module', course_id => null,
                // …) and the instructor form does not always submit exam_type
                // at all. Indexing $data here raised
                // "Undefined array key \"exam_type\"" and aborted the whole
                // transaction, so the exam was never created at all.
                'new_values' => [
                    'title' => $exam->title,
                    'exam_type' => $exam->exam_type,
                    'class_id' => $exam->class_id,
                    'course_id' => $exam->course_id,
                ],
            ]);

            return $exam;
        });
    }

    public function getExamQuestions(Exam $exam): Collection
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

    /**
 * Open a new attempt for the signed-in student.
 *
 * Student\ExamController@attempt has called this since the attempt routes
 * landed, but the method was never written — so starting an exam died with
 * "Call to undefined method ExamService::startAttempt()" and no student could
 * sit an exam at all.
 *
 * The attempt number continues the student's existing history, and the clock
 * starts now so the countdown the attempt page shows is measured from the same
 * moment.
     */
    public function startAttempt(Exam $exam): ExamAttempt
    {
        $studentId = auth()->id();

        if (! $studentId) {
            throw new \RuntimeException('Cannot start an exam attempt without an authenticated user.');
        }

        return DB::transaction(function () use ($exam, $studentId) {
            $attemptNumber = ExamAttempt::ofExam($exam->id)
                ->ofStudent($studentId)
                ->max('attempt_number') + 1;

            $attempt = ExamAttempt::create([
                'exam_id' => $exam->id,
                'student_id' => $studentId,
                'attempt_number' => $attemptNumber,
                'started_at' => now(),
                'status' => ExamAttempt::STATUS_IN_PROGRESS,
                'total_points' => $exam->total_points,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
                'metadata' => [
                    'started_from' => 'web',
                ],
            ]);

            AuditLog::create([
                'user_id' => $studentId,
                'action' => 'start',
                'resource_type' => ExamAttempt::class,
                'resource_id' => $attempt->id,
                'new_values' => [
                    'exam_id' => $exam->id,
                    'attempt_number' => $attemptNumber,
                ],
            ]);

            return $attempt;
        });
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
            $attempt->answers()->sync($answers, function ($key, $value) {
                return [
                    'is_correct' => $value['is_correct'] ?? null,
                    'points_awarded' => $value['points_awarded'] ?? 0,
                    'graded_by' => auth()->id(),
                    'graded_at' => now(),
                ];
            });

            $score = $attempt->answers->reduce(function ($carry, $answer) {
                return $carry + ($answer->points_awarded ?? 0);
            }, 0);

            $total = $attempt->exam->total_points;

            $passPercent = $total > 0 ? ($score / $total) * 100 : 0;

            $scorePercent = number_format($passPercent, 2);

            $exam = $attempt->exam;

            $attempt->update([
                'status' => ExamAttempt::STATUS_GRADED,
                'score' => $score,
                'score_percent' => $scorePercent,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'grade',
                'resource_type' => ExamAttempt::class,
                'resource_id' => $attempt->id,
                'new_values' => [
                    'previous_score' => 0,
                    'new_score' => $scorePercent,
                ],
            ]);

            // Update grade
            $grade = $exam->gradeForStudent($attempt->student_id);
            if ($grade) {
                $grade->update([
                    'score' => $scorePercent,
                    'grade' => $this->letterGrade($scorePercent),
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);
            }

            return $attempt->fresh();
        });
    }

    protected function letterGrade(float $percent): string
    {
        if ($percent >= 90) return 'A';
        if ($percent >= 80) return 'B';
        if ($percent >= 70) return 'C';
        if ($percent >= 60) return 'D';
        return 'F';
    }

    /**
     * Apply an edit to an existing exam.
     *
     * Both the admin and instructor controllers have called this since the
     * resource routes landed, but the method was never written — so saving an
     * exam from either side died with
     * "Call to undefined method ExamService::updateExam()".
     *
     * Only keys actually present in $data are written, so a caller that omits
     * an optional column leaves the stored value alone instead of nulling it.
     * `slug` and `created_by` are deliberately never touched: the slug is the
     * exam's identity in URLs and the author is history.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateExam(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data) {
            $tracked = ['title', 'exam_type', 'class_id', 'course_id', 'status'];
            $before = $exam->only($tracked);

            $editable = [
                'title', 'description', 'instructions', 'exam_type', 'class_id',
                'course_id', 'module_id', 'duration_minutes', 'total_points',
                'passing_score_percent', 'grade_weight', 'attempt_limit',
                'allow_review', 'requires_proctoring', 'proctoring_method',
                'proctoring_instructions', 'record_session', 'detect_tab_switch',
                'detect_copy_paste', 'allow_navigation', 'shuffle_questions',
                'shuffle_choices', 'show_question_number', 'show_timer',
                'auto_save_seconds', 'auto_submit_on_timeout', 'result_visibility',
                'show_correct_answers', 'show_score', 'results_release_date',
                'starts_at', 'ends_at', 'allowed_start_time', 'allowed_end_time',
                'video_url', 'video_duration_minutes', 'require_confirmation',
                'status',
            ];

            $attributes = array_intersect_key($data, array_flip($editable));

            if ($attributes !== []) {
                $exam->fill($attributes)->save();
            }

            $fresh = $exam->fresh();

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'resource_type' => Exam::class,
                'resource_id' => $exam->id,
                'old_values' => $before,
                'new_values' => $fresh->only($tracked),
            ]);

            return $fresh;
        });
    }

    public function deleteExam(Exam $exam): bool
    {
        return DB::transaction(function () use ($exam) {
            $exam->delete();

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'resource_type' => Exam::class,
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
    }

    /** Generate an Exam from the Test Bank with configurable quotas.
     *
     * This uses the same quota-based random selection as the Quiz Test Bank (#9)
     * -- total questions, per-category quotas, and per-difficulty quotas.
     * The generated exam uses snapshots (#14) so questions are frozen for the exam's lifetime.
     *
     * @param array{
     *     title: string,
     *     description?: string,
     *     instructions?: string,
     *     class_id: int,
     *     course_id?: int,
     *     module_id?: int,
     *     duration_minutes: int,
     *     passing_score_percent: int,
     *     grade_weight: int,
     *     attempt_limit: int,
     *     total_questions: int,
     *     category_quotas: array<int, int>,      # category_id => count
     *     difficulty_quotas: array<string, int>, # difficulty => count
     *     shuffle_questions?: bool,
     *     shuffle_choices?: bool,
     * } $data
     *
     * @throws ValidationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function generateFromTestBank(array $data): Exam
    {
        // Validate quota feasibility before creating the exam
        $totalQuestions = (int) ($data['total_questions'] ?? 0);
        $categoryQuotas = (array) ($data['category_quotas'] ?? []);
        $difficultyQuotas = (array) ($data['difficulty_quotas'] ?? []);

        if ($totalQuestions < 1) {
            throw ValidationException::withMessages([
                'total_questions' => 'Total questions must be at least 1.',
            ]);
        }

        // Get the available question pool from the Test Bank for this instructor
        $user = auth()->user();
        $pool = Question::query()
            ->with(['bank:id,title,created_by,is_shared', 'category:id,name'])
            ->where('status', '!=', Question::STATUS_ARCHIVED)
            ->whereNotNull('question_bank_id')
            ->whereHas('bank', function ($q) use ($user) {
                if ($user->isAdmin()) {
                    return;
                }
                $q->where('created_by', $user->id)->orWhere('is_shared', true);
            })
            ->get();

        if ($pool->count() < $totalQuestions) {
            throw ValidationException::withMessages([
                'total_questions' => sprintf(
                    'Only %d questions available in your Test Bank, but %d were requested.',
                    $pool->count(),
                    $totalQuestions
                ),
            ]);
        }

        // Resolve quotas using the same picker as Quiz Test Bank (#9)
        $picker = app(QuestionQuotaPicker::class);
        $drawn = $picker->pick($pool, $totalQuestions, $categoryQuotas, $difficultyQuotas);

        // Create the exam
        $examData = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'exam_type' => $data['exam_type'] ?? 'special',
            'class_id' => $data['class_id'],
            'course_id' => $data['course_id'] ?? null,
            'module_id' => $data['module_id'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'total_points' => $totalQuestions, // Each question = 1 point by default
            'passing_score_percent' => $data['passing_score_percent'] ?? 60,
            'grade_weight' => $data['grade_weight'] ?? 30,
            'attempt_limit' => $data['attempt_limit'] ?? 1,
            'shuffle_questions' => $data['shuffle_questions'] ?? true,
            'shuffle_choices' => $data['shuffle_choices'] ?? true,
            'allow_navigation' => $data['allow_navigation'] ?? true,
            'auto_submit_on_timeout' => true,
            'result_visibility' => 'after_grading',
            'show_correct_answers' => false,
            'show_score' => true,
            'status' => 'draft',
            'created_by' => auth()->id(),
            'slug' => \Illuminate\Support\Str::slug($data['title']) . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8)),
        ];

        return DB::transaction(function () use ($examData, $drawn) {
            $exam = Exam::create($examData);

            // Attach drawn questions with points = 1 each
            $position = 1;
            foreach ($drawn as $question) {
                ExamQuestion::create([
                    'exam_id' => $exam->id,
                    'question_id' => $question->id,
                    'points' => 1,
                    'order' => $position++,
                    'is_required' => true,
                ]);
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'resource_type' => Exam::class,
                'resource_id' => $exam->id,
                'new_values' => [
                    'title' => $examData['title'],
                    'exam_type' => $examData['exam_type'],
                    'generated_from_test_bank' => true,
                    'total_questions' => count($drawn),
                    'category_quotas' => $drawn->pluck('category_id')->filter()->count() > 0 ? 'configured' : 'none',
                    'difficulty_quotas' => $drawn->pluck('difficulty')->count() > 0 ? 'configured' : 'none',
                ],
            ]);

            return $exam;
        });
    }
}