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
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionChoice;
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
                'exam_type' => $data['exam_type'] ?? Exam::TYPE_PRELIM,
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

    /**
     * Import questions from a CSV or plain-text file onto an exam.
     *
     * Parsing is shared with the quiz importer through QuestionFileParser, so
     * both accept the same formats and produce the same questions; only the
     * pivot they land in differs.
     *
     * Each question is written to the exam's own bank first, so it stays
     * reusable on another exam instead of being stranded here.
     *
     * @return array{created: int, errors: array<int, string>}
     */
    public function importQuestionsFromFile(
        Exam $exam,
        int $userId,
        string $filePath,
        ?string $extension = null
    ): array {
        $parsed = app(QuestionFileParser::class)->parse($filePath, $extension);

        $errors = $parsed['errors'];
        $created = 0;
        $order = (int) ExamQuestion::where('exam_id', $exam->id)->max('order');

        DB::transaction(function () use ($parsed, $exam, $userId, &$created, &$order, &$errors) {
            $bank = $this->resolveBank($exam, $userId);
            $now = now();

            foreach ($parsed['records'] as $record) {
                try {
                    $question = Question::create([
                        'question_bank_id' => $bank->id,
                        'question_type' => $record['question_type'],
                        'question_text' => $record['question_text'],
                        'explanation' => $record['explanation'],
                        'difficulty' => $record['difficulty'],
                        'default_points' => $record['points'],
                        'tags' => $record['tags'],
                        'created_by' => $userId,
                        'status' => Question::STATUS_ACTIVE,
                    ]);

                    if ($record['choices'] !== []) {
                        $rows = [];
                        $seenCorrect = false;

                        foreach ($record['choices'] as $index => $choice) {
                            $isCorrect = (bool) $choice['is_correct'];
                            $seenCorrect = $seenCorrect || $isCorrect;

                            $rows[] = [
                                'question_id' => $question->id,
                                'choice_text' => $choice['choice_text'],
                                'is_correct' => $isCorrect,
                                'position' => $index + 1,
                                'points' => 0,
                                'feedback' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }

                        // A choice question with no answer cannot be graded, so
                        // it is reported rather than stored unusable.
                        if (! $seenCorrect) {
                            $errors[] = 'Skipped "'.$record['question_text'].'": no correct answer was marked.';

                            $question->delete();

                            continue;
                        }

                        QuestionChoice::insert($rows);
                    }

                    $order++;

                    ExamQuestion::create([
                        'exam_id' => $exam->id,
                        'question_id' => $question->id,
                        'order' => $order,
                        'points' => $record['points'],
                        'is_required' => true,
                    ]);

                    $created++;
                } catch (\Throwable $e) {
                    $errors[] = 'Could not import "'.$record['question_text'].'": '.$e->getMessage();
                }
            }
        });

        $exam->refresh();

        return compact('created', 'errors');
    }

    // ── Question management ───────────────────────────────────────
    //
    // An exam could be created but had no way to hold questions: the panel said
    // "attach from your question bank or add them manually" and neither was
    // wired up. These are the functions that make the exam usable.

    /**
     * Questions this instructor may put on an exam.
     *
     * Their own banks plus anything an admin has shared, mirroring what the
     * Test Bank library is allowed to show.
     *
     * @return \Illuminate\Support\Collection<int, Question>
     */
    public function availableQuestions(int $instructorId): Collection
    {
        return Question::query()
            ->where('status', '!=', Question::STATUS_ARCHIVED)
            ->whereHas('bank', fn ($q) => $q->where('created_by', $instructorId)
                ->orWhere('is_shared', true))
            ->with('choices', 'bank')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Link bank questions onto an exam.
     *
     * Questions already linked are skipped rather than duplicated, so clicking
     * "attach" twice cannot produce the same question twice.
     *
     * @param  array<int, int>  $questionIds
     * @param  array<int|string, float|int>  $points  Question id => points
     * @return int  Number actually attached
     */
    public function attachQuestions(Exam $exam, array $questionIds, array $points = [], ?int $instructorId = null): int
    {
        $questionIds = array_values(array_unique(array_filter(array_map('intval', $questionIds))));

        if ($questionIds === []) {
            return 0;
        }

        // Never trust posted ids: a question the instructor cannot see must not
        // be linkable just because its number was guessed.
        $permitted = $this->availableQuestions($instructorId ?? auth()->id())
            ->pluck('id')
            ->all();

        $attachable = array_values(array_intersect($questionIds, $permitted));

        if ($attachable === []) {
            return 0;
        }

        $existing = $exam->questions()->pluck('questions.id')->all();

        $nextOrder = (int) ExamQuestion::where('exam_id', $exam->id)->max('order');

        $attached = 0;

        foreach ($attachable as $questionId) {
            if (in_array($questionId, $existing, true)) {
                continue;
            }

            $nextOrder++;

            ExamQuestion::create([
                'exam_id' => $exam->id,
                'question_id' => $questionId,
                'order' => $nextOrder,
                // The exam's own weighting wins; the bank's default is only a
                // starting point.
                'points' => $points[$questionId] ?? Question::whereKey($questionId)->value('default_points') ?? 1,
                'is_required' => true,
            ]);

            $attached++;
        }

        $exam->refresh();

        return $attached;
    }

    /**
     * Create a question inline and link it to the exam in one step.
     *
     * The question is written to the instructor's bank for the exam's course so
     * it stays reusable rather than being stranded on one exam.
     *
     * @param  array<string, mixed>  $data
     */
    public function addManualQuestion(Exam $exam, int $userId, array $data): ExamQuestion
    {
        $question = DB::transaction(function () use ($exam, $userId, $data) {
            $bank = $this->resolveBank($exam, $userId);

            $question = Question::create([
                'question_bank_id' => $bank->id,
                'question_type' => $data['question_type'],
                'question_text' => $data['question_text'],
                'explanation' => $data['explanation'] ?? null,
                'difficulty' => $data['difficulty'] ?? Question::DIFFICULTY_MEDIUM,
                'default_points' => $data['points'] ?? 1,
                'tags' => $data['tags'] ?? [],
                'created_by' => $userId,
                'status' => Question::STATUS_ACTIVE,
            ]);

            foreach ($data['choices'] ?? [] as $index => $choice) {
                $text = is_array($choice) ? ($choice['choice_text'] ?? null) : $choice;

                if ($text === null || trim((string) $text) === '') {
                    continue;
                }

                QuestionChoice::create([
                    'question_id' => $question->id,
                    'choice_text' => trim((string) $text),
                    'is_correct' => (bool) (is_array($choice) ? ($choice['is_correct'] ?? false) : false),
                    'position' => $index + 1,
                    'points' => 0,
                    'feedback' => null,
                ]);
            }

            return $question;
        });

        $order = (int) ExamQuestion::where('exam_id', $exam->id)->max('order') + 1;

        $link = ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'order' => $order,
            'points' => $data['points'] ?? $question->default_points ?? 1,
            'is_required' => true,
        ]);

        $exam->refresh();

        return $link;
    }

    /**
     * Unlink a question from an exam, leaving the bank copy alone so it stays
     * reusable on other exams.
     */
    public function detachQuestion(Exam $exam, Question $question): bool
    {
        $removed = ExamQuestion::where('exam_id', $exam->id)
            ->where('question_id', $question->id)
            ->delete();

        // Close the gap the removal left so "order" stays 1..n.
        $this->renumber($exam);

        $exam->refresh();

        return $removed > 0;
    }

    /**
     * Set how many points one question is worth on this exam.
     */
    public function setQuestionPoints(Exam $exam, Question $question, float $points): bool
    {
        $updated = ExamQuestion::where('exam_id', $exam->id)
            ->where('question_id', $question->id)
            ->update(['points' => $points]);

        $exam->refresh();

        return $updated > 0;
    }

    /**
     * Reorder the exam's questions to match the given ids.
     *
     * Questions missing from the list keep their relative position at the end
     * rather than being dropped: reordering must never lose work.
     *
     * @param  array<int, int>  $orderedQuestionIds
     */
    public function reorderQuestions(Exam $exam, array $orderedQuestionIds): void
    {
        $ordered = array_values(array_unique(array_filter(array_map('intval', $orderedQuestionIds))));

        $current = ExamQuestion::where('exam_id', $exam->id)->orderBy('order')->get();

        $positions = [];

        $slot = 1;

        foreach ($ordered as $questionId) {
            $positions[$questionId] = $slot++;
        }

        foreach ($current as $link) {
            if (isset($positions[$link->question_id])) {
                continue;
            }

            $positions[$link->question_id] = $slot++;
        }

        foreach ($positions as $questionId => $position) {
            ExamQuestion::where('exam_id', $exam->id)
                ->where('question_id', $questionId)
                ->update(['order' => $position]);
        }

        $exam->refresh();
    }

    /** Rewrite order to a gapless 1..n sequence. */
    private function renumber(Exam $exam): void
    {
        $links = ExamQuestion::where('exam_id', $exam->id)->orderBy('order')->orderBy('id')->get();

        foreach ($links as $index => $link) {
            if ($link->order !== $index + 1) {
                $link->update(['order' => $index + 1]);
            }
        }
    }

    /**
     * The bank an exam's inline questions belong to: one per course, so repeated
     * adds collect in the same place instead of littering the bank list.
     */
    private function resolveBank(Exam $exam, int $userId): QuestionBank
    {
        $courseId = $exam->course_id ?? $exam->class?->course_id;

        return QuestionBank::firstOrCreate(
            [
                'course_id' => $courseId,
                'title' => ($exam->class?->course?->code ?? 'Exam').' Questions',
                'created_by' => $userId,
            ],
            [
                'description' => 'Questions added from exams.',
                'status' => 'active',
            ]
        );
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
            'exam_type' => $data['exam_type'] ?? Exam::TYPE_PRELIM,
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