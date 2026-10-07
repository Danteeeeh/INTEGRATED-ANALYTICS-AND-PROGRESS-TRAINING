<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionChoice;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuestionQuotaException;
use App\Services\QuestionQuotaPicker;
use App\Services\QuizImportService;
use App\Services\QuizExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuizController extends Controller
{
    public function index(Course $course): View
    {
        $this->authorize('viewAny', Quiz::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $quizzes = Quiz::whereHas('class.course', fn ($q) => $q->where('id', $course->id))
            ->orWhere(function ($q) use ($course) {
                $q->whereHas('module.course', fn ($q2) => $q2->where('id', $course->id));
            })
            ->orWhere(function ($q) use ($course) {
                $q->whereHas('lesson.module.course', fn ($q2) => $q2->where('id', $course->id));
            })
            ->with(['class', 'module', 'questions', 'attempts'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('instructor.courses.quizzes.index', compact('course', 'quizzes'));
    }

    public function create(Course $course): View
    {
        $this->authorize('create', Quiz::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $classes = ClassModel::where('course_id', $course->id)
            ->where('instructor_id', auth()->id())
            ->get();
        $modules = Module::where('course_id', $course->id)->with('lessons')->get();
        $resultVisibilityOptions = [
            Quiz::VISIBILITY_ALWAYS => 'Always Visible',
            Quiz::VISIBILITY_AFTER_GRADING => 'After Grading',
            Quiz::VISIBILITY_NEVER => 'Never Visible',
        ];

        // §8 — what the picker can offer for this course.
        $testBankQuestions = $this->testBankPool();

        return view('instructor.courses.quizzes.create', compact('course', 'classes', 'modules', 'resultVisibilityOptions', 'testBankQuestions'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('create', Quiz::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $isDraft = $request->input('status') === 'draft';

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'attempt_limit' => 'nullable|integer|min:1',
            'passing_score_percent' => 'nullable|integer|min:0|max:100',
            'shuffle_questions' => 'boolean',
            'shuffle_choices' => 'boolean',
            'allow_navigation' => 'boolean',
            'auto_save_seconds' => 'nullable|integer|min:30',
            'auto_submit_on_timeout' => 'boolean',
            'result_visibility' => $isDraft ? 'nullable|string|in:always,after_grading,never' : 'required|string|in:always,after_grading,never',
            'review_allowed' => 'boolean',
            'show_correct_answers' => 'boolean',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date|after:availability_from',
            'allowed_start_time' => 'nullable|date_format:H:i',
            'allowed_end_time' => 'nullable|date_format:H:i|after:allowed_start_time',
            'video_url' => 'nullable|url|max:500',
            'video_duration_minutes' => 'nullable|integer|min:1',
            'status' => 'required|string|in:draft,published,closed',
            'import_file' => 'nullable|file|mimes:csv,txt|max:'.config('lms.uploads.question_import'),
            'question_bank_id' => 'nullable|exists:question_banks,id',
            'questions' => 'nullable|array',
            'questions.*.text' => 'required_with:questions|string',
            'questions.*.points' => 'required_with:questions|integer|min:1',
            'questions.*.choices' => 'required_with:questions|array',
            'questions.*.choices.*' => 'required_with:questions|string',
            'questions.*.correct_choice' => 'required_with:questions|integer',

            // §8 — picking from the Test Bank links the existing row; it never
            // copies the question, so the original stays the single source.
            'test_bank_question_ids' => 'nullable|array',
            'test_bank_question_ids.*' => 'integer|exists:questions,id',
            'test_bank_points' => 'nullable|array',
            'test_bank_points.*' => 'integer|min:1|max:1000',

            // §9 — random draw: total + per-category + per-difficulty quotas.
            // selection_mode defaults to "manual" (tick by tick). When "random",
            // the draw is resolved by QuestionQuotaPicker before the quiz is
            // saved so a quota failure leaves no half-built quiz behind.
            'selection_mode' => 'nullable|string|in:manual,random',
            'draw_total' => 'nullable|integer|min:1',
            'draw_category' => 'nullable|array',
            'draw_category.*' => 'integer|min:0',
            'draw_difficulty' => 'nullable|array',
            'draw_difficulty.*' => 'integer|min:0',
            'test_bank_draw' => 'nullable|string',
        ]);

        if ($request->filled('class_id')) {
            $class = ClassModel::find($request->class_id);
            abort_if(! $class || $class->instructor_id !== auth()->id(), 403);
        }

        $validated['created_by'] = auth()->id();
        $validated['slug'] = Str::slug($validated['title']).'-'.Str::lower(Str::random(8));
        $validated['shuffle_questions'] = $validated['shuffle_questions'] ?? false;
        $validated['shuffle_choices'] = $validated['shuffle_choices'] ?? false;
        $validated['allow_navigation'] = $validated['allow_navigation'] ?? true;
        $validated['auto_submit_on_timeout'] = $validated['auto_submit_on_timeout'] ?? false;
        $validated['review_allowed'] = $validated['review_allowed'] ?? true;
        $validated['show_correct_answers'] = $validated['show_correct_answers'] ?? false;

        // §8/§9 — resolve the Test Bank selection before anything is written,
        // so a quota that cannot be met never leaves a half-built quiz behind.
        $selection = $this->resolveTestBankSelection($request, null);

        $quiz = Quiz::create($validated);

        // §8 — inline rows and Test Bank picks resolve together, and the
        // picker links the original question rather than copying it.
        $this->syncQuizQuestions($quiz, $request, $selection);

        // Import questions if file is provided
        if ($request->hasFile('import_file')) {
            try {
                $file = $request->file('import_file');
                $filePath = $file->getRealPath();
                $extension = strtolower($file->getClientOriginalExtension());

                $questionBank = $request->filled('question_bank_id')
                    ? QuestionBank::find($request->question_bank_id)
                    : null;

                $importService = new QuizImportService();
                $result = $importService->importQuestionsFromFile(
                    $filePath,
                    $quiz,
                    $request->user()->id,
                    $questionBank,
                    $extension
                );

                $message = "Quiz created successfully. Imported {$result['created']} questions.";
                if (! empty($result['errors'])) {
                    $message .= " Some rows had errors: " . implode('; ', array_slice($result['errors'], 0, 3));
                    if (count($result['errors']) > 3) {
                        $message .= " and " . (count($result['errors']) - 3) . " more.";
                    }
                }

                return redirect()->route('instructor.courses.quizzes.index', $course)
                    ->with('success', $message);
            } catch (\Exception $e) {
                return redirect()->route('instructor.courses.quizzes.index', $course)
                    ->with('success', 'Quiz created successfully, but question import failed: ' . $e->getMessage());
            }
        }

        $questionCount = $quiz->questions()->count();
        $message = $questionCount > 0
            ? "Quiz created successfully with {$questionCount} question(s)."
            : 'Quiz created successfully. Add questions to complete setup.';

        return redirect()->route('instructor.courses.quizzes.index', $course)
            ->with('success', $message);
    }

    public function show(Course $course, Quiz $quiz): View
    {
        $this->authorize('view', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $quiz->load(['class', 'module', 'lesson', 'questions.choices', 'attempts.student']);

        return view('instructor.courses.quizzes.show', compact('course', 'quiz'));
    }

    public function edit(Course $course, Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        // Load questions with their choices explicitly
        $quiz->load(['questions']);
        $quiz->questions->load('choices');

        $classes = ClassModel::where('course_id', $course->id)
            ->where('instructor_id', auth()->id())
            ->get();
        $modules = Module::where('course_id', $course->id)->with('lessons')->get();
        $resultVisibilityOptions = [
            Quiz::VISIBILITY_ALWAYS => 'Always Visible',
            Quiz::VISIBILITY_AFTER_GRADING => 'After Grading',
            Quiz::VISIBILITY_NEVER => 'Never Visible',
        ];

        // §8 — already-linked questions are excluded so the picker cannot
        // silently attach the same row twice.
        $testBankQuestions = $this->testBankPool($quiz);

        return view('instructor.courses.quizzes.edit', compact('course', 'quiz', 'classes', 'modules', 'resultVisibilityOptions', 'testBankQuestions'));
    }

    public function update(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $isDraft = $request->input('status') === 'draft';

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'attempt_limit' => 'nullable|integer|min:1',
            'passing_score_percent' => 'nullable|integer|min:0|max:100',
            'shuffle_questions' => 'boolean',
            'shuffle_choices' => 'boolean',
            'allow_navigation' => 'boolean',
            'auto_save_seconds' => 'nullable|integer|min:30',
            'auto_submit_on_timeout' => 'boolean',
            'result_visibility' => $isDraft ? 'nullable|string|in:always,after_grading,never' : 'required|string|in:always,after_grading,never',
            'review_allowed' => 'boolean',
            'show_correct_answers' => 'boolean',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date|after:availability_from',
            'allowed_start_time' => 'nullable|date_format:H:i',
            'allowed_end_time' => 'nullable|date_format:H:i|after:allowed_start_time',
            'video_url' => 'nullable|url|max:500',
            'video_duration_minutes' => 'nullable|integer|min:1',
            'status' => 'required|string|in:draft,published,closed',
            'import_file' => 'nullable|file|mimes:csv,txt|max:'.config('lms.uploads.question_import'),
            'question_bank_id' => 'nullable|exists:question_banks,id',
            'questions' => 'nullable|array',
            'questions.*.text' => 'required_with:questions|string',
            'questions.*.points' => 'required_with:questions|integer|min:1',
            'questions.*.choices' => 'required_with:questions|array',
            'questions.*.choices.*' => 'required_with:questions|string',
            'questions.*.correct_choice' => 'required_with:questions|integer',

            // §8 — picking from the Test Bank links the existing row; it never
            // copies the question, so the original stays the single source.
            'test_bank_question_ids' => 'nullable|array',
            'test_bank_question_ids.*' => 'integer|exists:questions,id',
            'test_bank_points' => 'nullable|array',
            'test_bank_points.*' => 'integer|min:1|max:1000',

            // §9 — random draw: total + per-category + per-difficulty quotas.
            // selection_mode defaults to "manual" (tick by tick). When "random",
            // the draw is resolved by QuestionQuotaPicker before the quiz is
            // saved so a quota failure leaves no half-built quiz behind.
            'selection_mode' => 'nullable|string|in:manual,random',
            'draw_total' => 'nullable|integer|min:1',
            'draw_category' => 'nullable|array',
            'draw_category.*' => 'integer|min:0',
            'draw_difficulty' => 'nullable|array',
            'draw_difficulty.*' => 'integer|min:0',
            'test_bank_draw' => 'nullable|string',
        ]);

        if ($request->filled('class_id')) {
            $class = ClassModel::find($request->class_id);
            abort_if(! $class || $class->instructor_id !== auth()->id(), 403);
        }

        $validated['shuffle_questions'] = $validated['shuffle_questions'] ?? false;
        $validated['shuffle_choices'] = $validated['shuffle_choices'] ?? false;
        $validated['allow_navigation'] = $validated['allow_navigation'] ?? true;
        $validated['auto_submit_on_timeout'] = $validated['auto_submit_on_timeout'] ?? false;
        $validated['review_allowed'] = $validated['review_allowed'] ?? true;
        $validated['show_correct_answers'] = $validated['show_correct_answers'] ?? false;

        // §8/§9 — resolve the Test Bank selection before any write, so a quota
        // that cannot be met never leaves the quiz half-edited.
        $selection = $this->resolveTestBankSelection($request, $quiz);

        $quiz->update($validated);

        // §8 — one sync instead of detach()+re-create. The old version copied
        // every question on every edit and orphaned the pivots past attempts
        // were graded against; links that survive are left exactly as they were.
        $this->syncQuizQuestions($quiz, $request, $selection);

        // Import questions if file is provided
        if ($request->hasFile('import_file')) {
            try {
                $file = $request->file('import_file');
                $filePath = $file->getRealPath();
                $extension = strtolower($file->getClientOriginalExtension());

                $questionBank = $request->filled('question_bank_id')
                    ? QuestionBank::find($request->question_bank_id)
                    : null;

                $importService = new QuizImportService();
                $result = $importService->importQuestionsFromFile(
                    $filePath,
                    $quiz,
                    $request->user()->id,
                    $questionBank,
                    $extension
                );

                $message = "Quiz updated successfully. Imported {$result['created']} questions.";
                if (! empty($result['errors'])) {
                    $message .= " Some rows had errors: " . implode('; ', array_slice($result['errors'], 0, 3));
                    if (count($result['errors']) > 3) {
                        $message .= " and " . (count($result['errors']) - 3) . " more.";
                    }
                }

                return redirect()->route('instructor.courses.quizzes.index', $course)
                    ->with('success', $message);
            } catch (\Exception $e) {
                return redirect()->route('instructor.courses.quizzes.index', $course)
                    ->with('success', 'Quiz updated successfully, but question import failed: ' . $e->getMessage());
            }
        }

        $questionCount = $quiz->questions()->count();
        $message = "Quiz updated successfully with {$questionCount} question(s).";

        return redirect()->route('instructor.courses.quizzes.index', $course)
            ->with('success', $message);
    }

    public function destroy(Course $course, Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $quiz->delete();

        return redirect()->route('instructor.courses.quizzes.index', $course)
            ->with('success', 'Quiz deleted successfully.');
    }

    public function publish(Course $course, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $quiz->update(['status' => Quiz::STATUS_PUBLISHED]);

        return redirect()->route('instructor.courses.quizzes.index', $course)
            ->with('success', 'Quiz published successfully.');
    }

    public function close(Course $course, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $quiz->update(['status' => Quiz::STATUS_CLOSED]);

        return redirect()->route('instructor.courses.quizzes.index', $course)
            ->with('success', 'Quiz closed successfully.');
    }

    public function attempts(Course $course, Quiz $quiz): View
    {
        $this->authorize('view', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $quiz->load('attempts.student');
        $attempts = $quiz->attempts()->with('student')->orderBy('created_at', 'desc')->paginate(20);

        return view('instructor.courses.quizzes.attempts', compact('course', 'quiz', 'attempts'));
    }

    public function showAttempt(Course $course, Quiz $quiz, QuizAttempt $attempt): View
    {
        $this->authorize('view', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($attempt->quiz_id !== $quiz->id, 404);

        $attempt->load('student', 'answers.question.choices', 'answers.selectedChoices');

        return view('instructor.courses.quizzes.attempt', compact('course', 'quiz', 'attempt'));
    }

    public function gradeAttempt(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($attempt->quiz_id !== $quiz->id, 404);

        $validated = $request->validate([
            'score' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        $attempt->update([
            'score' => $validated['score'],
            'score_percent' => isset($quiz->passing_score_percent) ? ($validated['score'] / 100) * 100 : null,
            'is_passed' => isset($quiz->passing_score_percent) && $attempt->score_percent >= $quiz->passing_score_percent,
            'status' => QuizAttempt::STATUS_GRADED,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        return redirect()->route('instructor.courses.quizzes.attempts.show', [$course, $quiz, $attempt])
            ->with('success', 'Attempt graded successfully.');
    }

    public function importQuestions(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'import_file' => 'required|file|mimes:csv,txt|max:'.config('lms.uploads.question_import'),
            'question_bank_id' => 'nullable|exists:question_banks,id',
        ]);

        $file = $request->file('import_file');
        $filePath = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $questionBank = $request->filled('question_bank_id')
                ? QuestionBank::find($request->question_bank_id)
                : null;

            $importService = new QuizImportService();
            $result = $importService->importQuestionsFromFile(
                $filePath,
                $quiz,
                $request->user()->id,
                $questionBank,
                $extension
            );

            $message = "Imported {$result['created']} questions successfully.";
            if (! empty($result['errors'])) {
                $message .= " Some rows had errors: " . implode('; ', array_slice($result['errors'], 0, 3));
                if (count($result['errors']) > 3) {
                    $message .= " and " . (count($result['errors']) - 3) . " more.";
                }
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Turn the inline question rows into links for the quiz (§8).
     *
     * Returns a list of question ids and their per-quiz points, and does *not*
     * touch the pivot itself — the caller syncs, so a question that is already
     * linked keeps its row and every past attempt keeps pointing at it.
     *
     * A row carrying its original question_id is edited in place rather than
     * duplicated. Editing a question the instructor does not own forks a copy
     * into their own bank instead of rewriting the shared original.
     *
     * @return list<array{id: int, points: int}>
     */
    protected function addQuestionsToQuiz(Quiz $quiz, array $questionsData, int $userId): array
    {
        $links = [];

        foreach ($questionsData as $questionData) {
            $points = (int) ($questionData['points'] ?? 1);
            $text = trim((string) ($questionData['text'] ?? ''));

            if ($text === '') {
                continue;
            }

            $original = ! empty($questionData['question_id'])
                ? Question::find((int) $questionData['question_id'])
                : null;

            $target = null;

            if ($original) {
                $originalText = trim((string) $original->question_text);
                $isMine = (int) ($original->bank?->created_by ?? 0) === $userId;

                if ($isMine) {
                    $this->updateQuestionFromRow($original, $questionData, $text);
                    $target = $original;
                } elseif ($text === $originalText) {
                    // Untouched shared question: link the original, no copy.
                    $target = $original;
                }
                // Someone else's question, and they changed the wording — fall
                // through and fork it below so the shared original is untouched.
            }

            if (! $target) {
                $target = $this->createQuestionForQuiz($quiz, $questionData, $text, $userId);
            }

            $links[] = [
                'id' => $target->id,
                'points' => $points,
                // The id the form thought it was editing. When a shared question
                // was forked this is the original, and the sync must not keep it.
                'original_id' => $original?->id,
            ];
        }

        return $links;
    }

    /**
     * Resolve the whole question list for a quiz in one pass (§8).
     *
     * Three inputs are merged in order: the inline rows in the form, whatever
     * is already linked but was *not* re-submitted (so Test Bank links survive
     * an ordinary edit), and the Test Bank picks. One sync() then replaces the
     * old detach-and-recreate dance — pivots for surviving questions are never
     * dropped, so every past attempt still resolves to the question it graded.
     */
    protected function syncQuizQuestions(Quiz $quiz, Request $request, array $pickedLinks = []): void
    {
        $userId = (int) $request->user()->id;

        // Read current pivots before touching anything: preserved links keep
        // the exact position and points they already had.
        $existing = $quiz->questions()->get()->keyBy('id');
        $currentPoints = $existing->mapWithKeys(
            fn ($q) => [$q->id => (int) ($q->pivot->points ?? 1)]
        );

        $links = [];   // question_id => ['position' => int, 'points' => int]
        $retired = []; // originals that were forked away and must not survive

        $inlineLinks = []; // id => points, in the order the form posted them
        $inlineOrder = [];

        if ($request->filled('questions')) {
            foreach ($this->addQuestionsToQuiz($quiz, $request->questions, $userId) as $entry) {
                $inlineLinks[$entry['id']] = $entry['points'];
                $inlineOrder[] = $entry['id'];

                if ($entry['original_id'] !== null && $entry['original_id'] !== $entry['id']) {
                    $retired[] = $entry['original_id'];
                }
            }
        }

        // The edit form flags itself as owning the whole list. When it does not
        // appear — a picker-only request, or anything that posts nothing about
        // inline rows — existing links are kept exactly as they are so nothing
        // is detached behind the caller's back. When it does, an inline row the
        // instructor removed really is meant to go.
        $preservedLinks = [];

        if (! $request->boolean('inline_questions_managed')) {
            foreach ($existing as $id => $question) {
                if (isset($inlineLinks[$id]) || in_array($id, $retired, true)) {
                    continue;
                }

                $preservedLinks[$id] = $currentPoints[$id] ?? 1;
            }
        }

        // Newly picked (or drawn, §9) from the Test Bank: link the original
        // row, never a copy.
        $picked = [];

        foreach ($pickedLinks as $id => $points) {
            if (isset($inlineLinks[$id]) || isset($picked[$id])) {
                continue;
            }

            $picked[$id] = $points;
        }

        // Kept links hold their old slots first so appending a pick does not
        // shuffle the order an attempt was taken in; then the inline rows in
        // form order, then the new picks.
        $position = 0;

        foreach ($preservedLinks as $id => $points) {
            $links[$id] = ['position' => ++$position, 'points' => $points];
        }

        foreach ($inlineOrder as $id) {
            $links[$id] = ['position' => ++$position, 'points' => $inlineLinks[$id]];
        }

        foreach ($picked as $id => $points) {
            $links[$id] = ['position' => ++$position, 'points' => $points];
        }

        if ($links === [] && ! $request->boolean('inline_questions_managed')) {
            return;
        }

        $quiz->questions()->sync($links);
    }

    /**
     * Work out which Test Bank questions this request wants, before anything
     * is written. Two modes: the individually ticked rows, or a §9 random draw
     * bounded by the category and difficulty quotas on the form.
     *
     * A quota that cannot be met raises a validation error, which is how the
     * form hears about it — there is no silent fallback to "whatever fits".
     *
     * @return array<int, int> question_id => points
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function resolveTestBankSelection(Request $request, ?Quiz $quiz): array
    {
        if ($request->input('selection_mode') !== 'random') {
            return $this->testBankLinks($request, $quiz);
        }

        $total = (int) $request->input('draw_total', 0);

        if ($total < 1) {
            throw ValidationException::withMessages([
                'test_bank_draw' => 'Say how many questions the drawn set should contain.',
            ]);
        }

        // A "0" category key means the form posted something unselectable;
        // uncategorised is posted as an empty string and filtered out with the
        // rest, since it has no quota of its own.
        $categoryQuotas = array_filter(
            (array) $request->input('draw_category', []),
            fn ($count, $id) => (int) $id > 0 && (int) $count > 0,
            ARRAY_FILTER_USE_BOTH
        );

        $difficultyQuotas = array_filter(
            (array) $request->input('draw_difficulty', []),
            fn ($count) => (int) $count > 0
        );

        try {
            $drawn = app(QuestionQuotaPicker::class)->pick(
                $this->testBankPool($quiz),
                $total,
                $categoryQuotas,
                $difficultyQuotas
            );
        } catch (QuestionQuotaException $e) {
            throw ValidationException::withMessages(['test_bank_draw' => $e->getMessage()]);
        }

        $points = (array) $request->input('test_bank_points', []);

        return collect($drawn)->mapWithKeys(fn (Question $question) => [
            $question->id => (int) ($points[$question->id] ?? $question->default_points ?? 1),
        ])->all();
    }

    /**
     * Question ids ticked in the picker, resolved through the same ownership
     * rule as the Test Bank tab so the picker can never reach someone else's
     * private work, even by posting an id directly.
     *
     * @return array<int, int> question_id => points
     */
    protected function testBankLinks(Request $request, ?Quiz $quiz = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            (array) $request->input('test_bank_question_ids', [])
        ))));

        if ($ids === []) {
            return [];
        }

        $points = (array) $request->input('test_bank_points', []);

        $links = [];

        foreach ($this->testBankPool($quiz)->whereIn('id', $ids) as $question) {
            $links[$question->id] = (int) ($points[$question->id]
                ?? $question->default_points
                ?? 1);
        }

        return $links;
    }

    /**
     * Reusable questions: own banks plus shared ones (everything for an admin),
     * minus what this quiz already uses. Archived questions stay out — §3 keeps
     * them out of anything newly served.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Question>
     */
    protected function testBankPool(?Quiz $quiz = null)
    {
        $user = auth()->user();

        $query = Question::query()
            ->with(['bank:id,title,created_by,is_shared', 'category:id,name'])
            ->where('status', '!=', Question::STATUS_ARCHIVED)
            ->whereNotNull('question_bank_id')
            ->orderBy('id');

        if (! $user->isAdmin()) {
            $query->whereHas('bank', fn ($q) => $q
                ->where('created_by', $user->id)
                ->orWhere('is_shared', true));
        }

        if ($quiz) {
            $query->whereDoesntHave('quizzes', fn ($q) => $q->where('quizzes.id', $quiz->id));
        }

        // Own course first would hide the cross-course pool, so keep every
        // usable question and let the search box on the form narrow it.
        return $query->take(300)->get();
    }

    /**
     * @param  array<string, mixed>  $questionData
     */
    private function updateQuestionFromRow(Question $question, array $questionData, string $text): void
    {
        $question->update([
            'question_text' => $text,
            'default_points' => (int) ($questionData['points'] ?? $question->default_points),
        ]);

        $this->syncRowChoices($question, $questionData);
    }

    /**
     * Build a brand-new question (or a fork of a shared one) for this quiz.
     *
     * It still lands in a bank, so it becomes reusable from the Test Bank the
     * next time — that is the point of the section (§8).
     *
     * @param  array<string, mixed>  $questionData
     */
    private function createQuestionForQuiz(Quiz $quiz, array $questionData, string $text, int $userId): Question
    {
        $courseId = $quiz->class?->course_id
            ?? $quiz->module?->course_id
            ?? $quiz->lesson?->module?->course_id;

        $course = $courseId ? Course::find($courseId) : null;

        // One bank per course, not one per quiz: naming it after the quiz
        // littered the bank list with "{Quiz Title} Questions" every time.
        $title = $course
            ? trim($course->code.' Questions')
            : trim($quiz->title.' Questions');

        $questionBank = QuestionBank::firstOrCreate(
            [
                'course_id' => $courseId,
                'title' => $title,
                'created_by' => $userId,
            ],
            [
                'description' => 'Authored while building quizzes for '.($course ? $course->title : $quiz->title),
                'status' => 'active',
            ]
        );

        $question = Question::create([
            'question_bank_id' => $questionBank->id,
            'question_type' => 'multiple_choice',
            'question_text' => $text,
            'default_points' => $questionData['points'] ?? 1,
            'difficulty' => 'medium',
            'created_by' => $userId,
            'status' => 'active',
        ]);

        $this->syncRowChoices($question, $questionData);

        return $question;
    }

    /**
     * The inline form posts a fixed number of choice slots, some of them blank.
     *
     * Slots are keyed exactly as `correct_choice` refers to them (the form posts
     * `choices[1..4]` and a matching radio value), so the answer key is matched
     * against the original key rather than the loop position — otherwise the
     * selected answer lands on the row beside it. Positions are then assigned
     * 1..n, which also closes the gap the old code left at the head of the list.
     *
     * @param  array<string, mixed>  $questionData
     */
    private function syncRowChoices(Question $question, array $questionData): void
    {
        $slots = [];

        foreach ((array) ($questionData['choices'] ?? []) as $key => $choiceText) {
            $text = trim((string) $choiceText);

            if ($text !== '') {
                $slots[] = ['key' => (int) $key, 'text' => $text];
            }
        }

        if ($slots === []) {
            return;
        }

        $correctKey = (int) ($questionData['correct_choice'] ?? 1);
        $position = 1;

        foreach ($slots as $slot) {
            QuestionChoice::updateOrCreate(
                ['question_id' => $question->id, 'position' => $position],
                [
                    'choice_text' => $slot['text'],
                    'is_correct' => $slot['key'] === $correctKey,
                ]
            );

            $position++;
        }

        // Drop leftovers from a previous, longer choice list.
        QuestionChoice::where('question_id', $question->id)
            ->where('position', '>=', $position)
            ->delete();
    }

    public function grantExtension(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        $this->authorize('extendDeadline', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'extended_until' => 'required|date|after:now',
            'reason' => 'nullable|string',
        ]);

        $studentId = $validated['student_id'];

        // Check if student is enrolled in the course
        $enrollment = \App\Models\Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'Student is not enrolled in this course.');
        }

        // Update or create extension
        \App\Models\QuizExtension::updateOrCreate(
            [
                'quiz_id' => $quiz->id,
                'student_id' => $studentId,
            ],
            [
                'extended_until' => $validated['extended_until'],
                'reason' => $validated['reason'] ?? null,
                'granted_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Quiz extension granted successfully.');
    }

    public function revokeExtension(Course $course, Quiz $quiz, int $studentId): RedirectResponse
    {
        $this->authorize('extendDeadline', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $extension = \App\Models\QuizExtension::where('quiz_id', $quiz->id)
            ->where('student_id', $studentId)
            ->first();

        if ($extension) {
            $extension->delete();
        }

        return back()->with('success', 'Quiz extension revoked successfully.');
    }

    /**
     * Export quiz questions to plain text format with asterisks marking correct answers
     */
    public function exportText(Course $course, Quiz $quiz): StreamedResponse
    {
        $this->authorize('view', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $exportService = new QuizExportService();
        $content = $exportService->exportToPlainText($quiz);

        $fileName = Str::slug($quiz->title) . '-questions.txt';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $fileName, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Export quiz questions to CSV format
     */
    public function exportCsv(Course $course, Quiz $quiz): StreamedResponse
    {
        $this->authorize('view', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $exportService = new QuizExportService();
        $content = $exportService->exportToCsv($quiz);

        $fileName = Str::slug($quiz->title) . '-questions.csv';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Export quiz with student answers
     */
    public function exportWithAnswers(Course $course, Quiz $quiz, int $studentId): StreamedResponse
    {
        $this->authorize('view', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $exportService = new QuizExportService();
        $content = $exportService->exportWithStudentAnswers($quiz, $studentId);

        $fileName = Str::slug($quiz->title) . '-student-answers.txt';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $fileName, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
