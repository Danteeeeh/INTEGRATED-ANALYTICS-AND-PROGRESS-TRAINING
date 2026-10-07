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
                'new_values' => [
                    'title' => $data['title'],
                    'exam_type' => $data['exam_type'],
                    'class_id' => $data['class_id'],
                    'course_id' => $data['course_id'],
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