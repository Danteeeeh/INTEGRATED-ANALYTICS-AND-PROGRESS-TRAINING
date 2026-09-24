<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Quiz::class);

        $query = Quiz::with(['class.course', 'creator']);

        if ($request->filled('course_id')) {
            $query->whereHas('class', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
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
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('instructions', 'like', "%{$search}%");
            });
        }

        $quizzes = $query->orderByDesc('created_at')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.quizzes.index', compact('quizzes', 'courses', 'classes'));
    }

    public function create(): View
    {
        $this->authorize('create', Quiz::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.quizzes.create', compact('courses', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Quiz::class);

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
            'auto_save_seconds' => 'nullable|integer|min:0',
            'auto_submit_on_timeout' => 'boolean',
            'result_visibility' => 'required|in:always,after_grading,never',
            'review_allowed' => 'boolean',
            'show_correct_answers' => 'boolean',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date',
            'status' => 'required|in:draft,published,closed',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['shuffle_questions'] = $request->boolean('shuffle_questions', false);
        $validated['shuffle_choices'] = $request->boolean('shuffle_choices', false);
        $validated['allow_navigation'] = $request->boolean('allow_navigation', true);
        $validated['auto_submit_on_timeout'] = $request->boolean('auto_submit_on_timeout', true);
        $validated['review_allowed'] = $request->boolean('review_allowed', false);
        $validated['show_correct_answers'] = $request->boolean('show_correct_answers', false);

        $quiz = Quiz::create($validated);

        session()->flash('success', 'Quiz created successfully.');

        return redirect()->route('admin.quizzes.show', $quiz);
    }

    public function show(Quiz $quiz): View
    {
        $this->authorize('view', $quiz);

        $quiz->load(['class.course', 'module', 'lesson', 'creator', 'questions.choices', 'attempts.student']);

        return view('admin.quizzes.show', compact('quiz'));
    }

    public function edit(Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.quizzes.edit', compact('quiz', 'courses', 'classes'));
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

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
            'auto_save_seconds' => 'nullable|integer|min:0',
            'auto_submit_on_timeout' => 'boolean',
            'result_visibility' => 'required|in:always,after_grading,never',
            'review_allowed' => 'boolean',
            'show_correct_answers' => 'boolean',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date',
            'status' => 'required|in:draft,published,closed',
        ]);

        $validated['shuffle_questions'] = $request->boolean('shuffle_questions', $quiz->shuffle_questions);
        $validated['shuffle_choices'] = $request->boolean('shuffle_choices', $quiz->shuffle_choices);
        $validated['allow_navigation'] = $request->boolean('allow_navigation', $quiz->allow_navigation);
        $validated['auto_submit_on_timeout'] = $request->boolean('auto_submit_on_timeout', $quiz->auto_submit_on_timeout);
        $validated['review_allowed'] = $request->boolean('review_allowed', $quiz->review_allowed);
        $validated['show_correct_answers'] = $request->boolean('show_correct_answers', $quiz->show_correct_answers);

        $quiz->update($validated);

        session()->flash('success', 'Quiz updated successfully.');

        return redirect()->route('admin.quizzes.show', $quiz);
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        try {
            if ($quiz->attempts()->exists()) {
                return back()->with('error', 'Cannot delete a quiz that has attempts.');
            }
            $quiz->delete();
            session()->flash('success', 'Quiz archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.quizzes.index');
    }

    public function publish(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $quiz->update(['status' => Quiz::STATUS_PUBLISHED]);

        session()->flash('success', 'Quiz published successfully.');

        return back();
    }

    public function close(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $quiz->update(['status' => Quiz::STATUS_CLOSED]);

        session()->flash('success', 'Quiz closed successfully.');

        return back();
    }
}
