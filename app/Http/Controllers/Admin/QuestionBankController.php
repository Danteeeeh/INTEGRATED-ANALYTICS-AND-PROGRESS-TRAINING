<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\QuestionBank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionBankController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', QuestionBank::class);

        $query = QuestionBank::with(['course', 'class', 'creator']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        return view('admin.question-banks.index', compact('questionBanks', 'courses', 'classes'));
    }

    public function create(): View
    {
        $this->authorize('create', QuestionBank::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.question-banks.create', compact('courses', 'classes'));
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

        $validated['created_by'] = $request->user()->id;
        $validated['is_shared'] = $request->boolean('is_shared', false);

        $questionBank = QuestionBank::create($validated);

        session()->flash('success', 'Question bank created successfully.');

        return redirect()->route('admin.question_banks.show', $questionBank);
    }

    public function show(QuestionBank $questionBank): View
    {
        $this->authorize('view', $questionBank);

        $questionBank->load(['course', 'class', 'creator', 'questions.choices']);

        return view('admin.question-banks.show', compact('questionBank'));
    }

    public function edit(QuestionBank $questionBank): View
    {
        $this->authorize('update', $questionBank);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.question-banks.edit', compact('questionBank', 'courses', 'classes'));
    }

    public function update(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->authorize('update', $questionBank);

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

        session()->flash('success', 'Question bank updated successfully.');

        return redirect()->route('admin.question_banks.show', $questionBank);
    }

    public function destroy(QuestionBank $questionBank): RedirectResponse
    {
        $this->authorize('delete', $questionBank);

        try {
            if ($questionBank->questions()->exists()) {
                return back()->with('error', 'Cannot delete a question bank that still has questions.');
            }
            $questionBank->delete();
            session()->flash('success', 'Question bank archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.question_banks.index');
    }
}
