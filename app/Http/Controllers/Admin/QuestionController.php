<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Question::class);

        $query = Question::with(['bank', 'creator', 'choices']);

        if ($request->filled('question_bank_id')) {
            $query->where('question_bank_id', $request->question_bank_id);
        }

        if ($request->filled('question_type')) {
            $query->where('question_type', $request->question_type);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('question_text', 'like', "%{$search}%")
                    ->orWhere('explanation', 'like', "%{$search}%");
            });
        }

        $questions = $query->orderByDesc('created_at')->paginate(15);
        $questionBanks = QuestionBank::active()->orderBy('title')->get(['id', 'title']);

        return view('admin.questions.index', compact('questions', 'questionBanks'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Question::class);

        $questionBanks = QuestionBank::active()->orderBy('title')->get(['id', 'title']);
        $preselectedBankId = $request->get('question_bank_id');

        return view('admin.questions.create', compact('questionBanks', 'preselectedBankId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Question::class);

        $validated = $request->validate([
            'question_bank_id' => 'required|exists:question_banks,id',
            'question_type' => 'required|in:multiple_choice,multiple_answer,true_false,identification,short_answer,essay',
            'question_text' => 'required|string',
            'explanation' => 'nullable|string',
            'difficulty' => 'required|in:easy,medium,hard',
            'default_points' => 'nullable|numeric|min:0',
            'tags' => 'nullable|array',
            'status' => 'required|string|max:50',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['tags'] = $request->input('tags', []);

        $question = Question::create($validated);

        session()->flash('success', 'Question created successfully.');

        return redirect()->route('admin.questions.show', $question);
    }

    public function show(Question $question): View
    {
        $this->authorize('view', $question);

        $question->load(['bank', 'creator', 'choices', 'quizzes']);

        return view('admin.questions.show', compact('question'));
    }

    public function edit(Question $question): View
    {
        $this->authorize('update', $question);

        $questionBanks = QuestionBank::active()->orderBy('title')->get(['id', 'title']);

        return view('admin.questions.edit', compact('question', 'questionBanks'));
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $this->authorize('update', $question);

        $validated = $request->validate([
            'question_bank_id' => 'required|exists:question_banks,id',
            'question_type' => 'required|in:multiple_choice,multiple_answer,true_false,identification,short_answer,essay',
            'question_text' => 'required|string',
            'explanation' => 'nullable|string',
            'difficulty' => 'required|in:easy,medium,hard',
            'default_points' => 'nullable|numeric|min:0',
            'tags' => 'nullable|array',
            'status' => 'required|string|max:50',
        ]);

        $validated['tags'] = $request->input('tags', $question->tags);

        $question->update($validated);

        session()->flash('success', 'Question updated successfully.');

        return redirect()->route('admin.questions.show', $question);
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        try {
            if ($question->quizzes()->exists()) {
                return back()->with('error', 'Cannot delete a question that is attached to quizzes.');
            }
            if ($question->quizAnswers()->exists()) {
                return back()->with('error', 'Cannot delete a question that has been answered.');
            }
            $question->delete();
            session()->flash('success', 'Question archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.questions.index');
    }
}
