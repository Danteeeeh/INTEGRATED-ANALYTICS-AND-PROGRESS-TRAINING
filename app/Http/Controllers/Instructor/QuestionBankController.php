<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionCategory;
use App\Models\QuestionChoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuestionBankController extends Controller
{
    /**
     * Show the instructor's own banks plus any bank an admin has shared.
     *
     * Previously this was scoped to `created_by = auth()->id()` only, which is
     * why an instructor never saw the banks created under /admin/question-banks.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', QuestionBank::class);

        $user = auth()->user();

        $query = QuestionBank::with(['course', 'class', 'creator'])
            ->withCount('questions')
            ->where(function ($q) use ($user) {
                // Own banks first, then any bank the admin has flagged as shared.
                $q->where('created_by', $user->id)
                    ->orWhere('is_shared', true);
            });

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('scope')) {
            $scope = $request->scope;
            $query->when(
                $scope === 'mine',
                fn ($q) => $q->where('created_by', $user->id),
                fn ($q) => $q->when($scope === 'shared', fn ($qq) => $qq->where('is_shared', true))
            );
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $questionBanks = $query->orderByDesc('created_at')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('instructor.question-banks.index', compact('questionBanks', 'courses', 'classes'));
    }

    public function create(): View
    {
        $this->authorize('create', QuestionBank::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('instructor.question-banks.create', compact('courses', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', QuestionBank::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:question_banks,code',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'is_shared' => 'boolean',
            'status' => 'required|in:draft,active,archived',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_shared'] = $request->boolean('is_shared', false);

        $questionBank = QuestionBank::create($validated);

        session()->flash('success', 'Question bank created successfully. Add your questions next.');

        return redirect()->route('instructor.question_banks.edit', $questionBank);
    }

    public function show(QuestionBank $questionBank): View
    {
        $this->authorize('view', $questionBank);

        if ($questionBank->created_by !== auth()->id() && ! $questionBank->is_shared) {
            abort(403);
        }

        $questionBank->load([
            'course',
            'class',
            'creator',
            'questions' => fn ($q) => $q->with('choices')->orderBy('id'),
        ]);

        return view('instructor.question-banks.show', compact('questionBank'));
    }

    public function edit(QuestionBank $questionBank): View
    {
        $this->authorize('update', $questionBank);

        // Only the owner may edit bank metadata and its questions.
        if ($questionBank->created_by !== auth()->id()) {
            abort(403, 'You can only edit question banks that you created.');
        }

        $questionBank->load([
            'course',
            'class',
            'questions' => fn ($q) => $q->with('choices')->orderBy('id'),
        ]);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        // §7 — the category picker on every question form below.
        $categories = QuestionCategory::orderBy('name')->get(['id', 'name', 'course_id']);

        return view('instructor.question-banks.edit', compact('questionBank', 'courses', 'classes', 'categories'));
    }

    public function update(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->authorize('update', $questionBank);

        if ($questionBank->created_by !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:question_banks,code,'.$questionBank->id,
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'is_shared' => 'boolean',
            'status' => 'required|in:draft,active,archived',
        ]);

        $validated['is_shared'] = $request->boolean('is_shared', $questionBank->is_shared);

        $questionBank->update($validated);

        return redirect()->route('instructor.question_banks.edit', $questionBank)
            ->with('success', 'Question bank updated successfully.');
    }

    public function destroy(QuestionBank $questionBank): RedirectResponse
    {
        $this->authorize('delete', $questionBank);

        if ($questionBank->created_by !== auth()->id()) {
            abort(403);
        }

        try {
            if ($questionBank->questions()->exists()) {
                return back()->with('error', 'Cannot delete a question bank that still has questions. Remove them first.');
            }
            $questionBank->delete();
            session()->flash('success', 'Question bank archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('instructor.question_banks.index');
    }

    // ── Questions ────────────────────────────────────────────────────────────

    public function storeQuestion(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->authorize('create', QuestionBank::class);

        abort_if($questionBank->created_by !== auth()->id(), 403);

        $payload = $this->validateQuestionPayload($request);

        DB::transaction(function () use ($questionBank, $payload) {
            $question = Question::create([
                'question_bank_id' => $questionBank->id,
                'question_type' => $payload['question_type'],
                'question_text' => $payload['question_text'],
                'explanation' => $payload['explanation'] ?? null,
                'difficulty' => $payload['difficulty'],
                'default_points' => $payload['default_points'],
                'tags' => $payload['tags'] ?? null,
                'status' => $payload['status'],
                'category_id' => $payload['category_id'] ?? null,
                'is_case_sensitive' => $payload['is_case_sensitive'] ?? false,
                'created_by' => auth()->id(),
            ]);

            $this->syncChoices($question, $payload);
        });

        return redirect()->route('instructor.question_banks.edit', $questionBank)
            ->with('success', 'Question added.');
    }

    public function updateQuestion(Request $request, QuestionBank $questionBank, Question $question): RedirectResponse
    {
        $this->authorize('update', $question);

        abort_if($questionBank->created_by !== auth()->id(), 403);
        abort_if($question->question_bank_id !== $questionBank->id, 404);

        $payload = $this->validateQuestionPayload($request, $question);

        DB::transaction(function () use ($question, $payload) {
            $question->update([
                'question_type' => $payload['question_type'],
                'question_text' => $payload['question_text'],
                'explanation' => $payload['explanation'] ?? null,
                'difficulty' => $payload['difficulty'],
                'default_points' => $payload['default_points'],
                'tags' => $payload['tags'] ?? null,
                'status' => $payload['status'],
                'category_id' => $payload['category_id'] ?? null,
                'is_case_sensitive' => $payload['is_case_sensitive'] ?? false,
            ]);

            $this->syncChoices($question, $payload);
        });

        return redirect()->route('instructor.question_banks.edit', $questionBank)
            ->with('success', 'Question updated.');
    }

    public function destroyQuestion(QuestionBank $questionBank, Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        abort_if($questionBank->created_by !== auth()->id(), 403);
        abort_if($question->question_bank_id !== $questionBank->id, 404);

        // §5 — deactivate rather than destroy. quiz_questions/exam_questions use
        // a cascading FK, so a hard delete here would rewrite past attempts.
        // The question is only trashed (soft delete), and only while nothing
        // points at it; otherwise the caller is told to archive it first.
        if ($question->isInUse()) {
            return back()->with(
                'error',
                'This question is already used by a quiz or exam. Archive it instead of deleting.'
            );
        }

        $question->delete();

        return redirect()->route('instructor.question_banks.edit', $questionBank)
            ->with('success', 'Question deleted.');
    }

    /**
     * Validate a question plus its choices.
     */
    protected function validateQuestionPayload(Request $request, ?Question $question = null): array
    {
        $data = $request->validate([
            'question_type' => 'required|string|in:'.implode(',', [
                Question::TYPE_MULTIPLE_CHOICE,
                Question::TYPE_MULTIPLE_ANSWER,
                Question::TYPE_TRUE_FALSE,
                Question::TYPE_IDENTIFICATION,
                Question::TYPE_SHORT_ANSWER,
                Question::TYPE_ESSAY,
            ]),
            'question_text' => 'required|string|max:5000',
            'explanation' => 'nullable|string|max:5000',
            'difficulty' => 'required|string|in:easy,medium,hard',
            'default_points' => 'required|numeric|min:0|max:1000',
            'tags' => 'nullable|string|max:255',
            'status' => 'required|string|in:draft,active,archived',

            // §7 — topic grouping, which the quota builder (§9) also reads.
            'category_id' => 'nullable|integer|exists:question_categories,id',
            // §2 — only meaningful for free-text types.
            'is_case_sensitive' => 'nullable',

            'choice' => 'nullable|array',
            'choice.*.text' => 'nullable|string|max:1000',
            'choice.*.correct' => 'nullable',

            'new' => 'nullable|array',
            'new.*.text' => 'nullable|string|max:1000',
            'new.*.correct' => 'nullable',
        ], [], [
            'question_type' => 'question type',
            'question_text' => 'question text',
        ]);

        $data['tags'] = $this->normaliseTags($data['tags'] ?? null);
        $data['category_id'] = (int) ($data['category_id'] ?? 0) ?: null;
        $data['is_case_sensitive'] = $this->isTruthy($data['is_case_sensitive'] ?? null);

        // Resolve the final choice set, dropping blank rows.
        $existing = [];
        foreach (($data['choice'] ?? []) as $id => $row) {
            $text = trim((string) ($row['text'] ?? ''));
            $existing[] = [
                'id' => (int) $id,
                'text' => $text,
                'correct' => $this->isTruthy($row['correct'] ?? null),
            ];
        }

        $new = [];
        foreach (($data['new'] ?? []) as $row) {
            $text = trim((string) ($row['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $new[] = ['text' => $text, 'correct' => $this->isTruthy($row['correct'] ?? null)];
        }

        $data['existing_choices'] = $existing;
        $data['new_choices'] = $new;

        $this->validateChoiceSet($data['question_type'], array_merge(
            array_values(array_filter($existing, fn ($c) => $c['text'] !== '')),
            $new
        ));

        return $data;
    }

    /**
     * Ensure a question cannot be saved in a state that can never be graded.
     */
    protected function validateChoiceSet(string $type, array $choices): void
    {
        $needsChoices = in_array($type, [
            Question::TYPE_MULTIPLE_CHOICE,
            Question::TYPE_MULTIPLE_ANSWER,
            Question::TYPE_TRUE_FALSE,
        ], true);

        if (! $needsChoices) {
            return;
        }

        if (count($choices) < 2) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'question_text' => 'This question type needs at least 2 choices.',
            ]);
        }

        $correctCount = count(array_filter($choices, fn ($c) => $c['correct']));

        if ($correctCount === 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'question_text' => 'Mark at least one choice as correct.',
            ]);
        }

        if ($type !== Question::TYPE_MULTIPLE_ANSWER && $correctCount > 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'question_text' => $type === Question::TYPE_TRUE_FALSE
                    ? 'A true/false question can only have one correct choice.'
                    : 'Only one choice can be correct for a multiple choice question.',
            ]);
        }
    }

    /**
     * Create/update/delete choices so the stored set matches what was submitted.
     */
    protected function syncChoices(Question $question, array $payload): void
    {
        $position = 0;

        foreach ($payload['existing_choices'] as $choice) {
            if ($choice['text'] === '') {
                // Blanking a row out removes that choice.
                QuestionChoice::where('question_id', $question->id)
                    ->where('id', $choice['id'])
                    ->delete();
                continue;
            }

            QuestionChoice::where('question_id', $question->id)
                ->where('id', $choice['id'])
                ->update([
                    'choice_text' => $choice['text'],
                    'is_correct' => $choice['correct'],
                    'position' => $position++,
                ]);
        }

        foreach ($payload['new_choices'] as $choice) {
            QuestionChoice::create([
                'question_id' => $question->id,
                'choice_text' => $choice['text'],
                'is_correct' => $choice['correct'],
                'position' => $position++,
                'points' => 0,
            ]);
        }

        $question->unsetRelation('choices');
    }

    protected function normaliseTags(?string $tags): ?array
    {
        $tags = trim((string) $tags);

        if ($tags === '') {
            return null;
        }

        $tags = array_values(array_filter(array_map(
            fn ($t) => trim($t),
            preg_split('/[,\n]+/', $tags) ?: []
        )));

        return $tags ?: null;
    }

    protected function isTruthy($value): bool
    {
        return in_array($value, ['1', 1, 'on', 'true', true], true);
    }
}