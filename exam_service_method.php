/**
     * Generate an Exam from the Test Bank with configurable quotas.
     *
     * This uses the same quota-based random selection as the Quiz Test Bank
     * (§9) — total questions, per-category quotas, and per-difficulty quotas.
     * The generated exam uses snapshots (§14) so questions are frozen for
     * the exam's lifetime.
     *
     * @param  array{
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
     *     category_quotas: array<int, int>,      // category_id => count
     *     difficulty_quotas: array<string, int>, // difficulty => count
     *     shuffle_questions?: bool,
     *     shuffle_choices?: bool,
     * }  $data
     *
     * @throws \App\Services\QuestionQuotaException
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

        // Resolve quotas using the same picker as Quiz Test Bank (§9)
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