<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Rubric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Assignment::class);

        $query = Assignment::with(['class.course', 'creator', 'rubric']);

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
                    ->orWhere('instructions', 'like', "%{$search}%");
            });
        }

        $assignments = $query->orderByDesc('created_at')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.assignments.index', compact('assignments', 'courses', 'classes'));
    }

    public function create(): View
    {
        $this->authorize('create', Assignment::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $rubrics = Rubric::active()->orderBy('title')->get(['id', 'title']);

        return view('admin.assignments.create', compact('courses', 'classes', 'rubrics'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Assignment::class);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'instructions' => 'required|string',
            'points' => 'required|integer|min:0',
            'submission_type' => 'required|in:text,file,multiple_files',
            'due_date' => 'nullable|date',
            'allow_late' => 'boolean',
            'late_submission_deduction_percent' => 'nullable|integer|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1',
            'allow_resubmission' => 'boolean',
            'resubmission_deadline' => 'nullable|date',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date',
            'rubric_id' => 'nullable|exists:rubrics,id',
            'status' => 'required|in:draft,published,closed',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['allow_late'] = $request->boolean('allow_late', false);
        $validated['allow_resubmission'] = $request->boolean('allow_resubmission', false);

        $assignment = Assignment::create($validated);

        session()->flash('success', 'Assignment created successfully.');

        return redirect()->route('admin.assignments.show', $assignment);
    }

    public function show(Assignment $assignment): View
    {
        $this->authorize('view', $assignment);

        $assignment->load(['class.course', 'module', 'lesson', 'creator', 'rubric.criteria', 'attachments', 'submissions.student']);

        return view('admin.assignments.show', compact('assignment'));
    }

    public function edit(Assignment $assignment): View
    {
        $this->authorize('update', $assignment);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $rubrics = Rubric::active()->orderBy('title')->get(['id', 'title']);

        return view('admin.assignments.edit', compact('assignment', 'courses', 'classes', 'rubrics'));
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'instructions' => 'required|string',
            'points' => 'required|integer|min:0',
            'submission_type' => 'required|in:text,file,multiple_files',
            'due_date' => 'nullable|date',
            'allow_late' => 'boolean',
            'late_submission_deduction_percent' => 'nullable|integer|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1',
            'allow_resubmission' => 'boolean',
            'resubmission_deadline' => 'nullable|date',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date',
            'rubric_id' => 'nullable|exists:rubrics,id',
            'status' => 'required|in:draft,published,closed',
        ]);

        $validated['allow_late'] = $request->boolean('allow_late', $assignment->allow_late);
        $validated['allow_resubmission'] = $request->boolean('allow_resubmission', $assignment->allow_resubmission);

        $assignment->update($validated);

        session()->flash('success', 'Assignment updated successfully.');

        return redirect()->route('admin.assignments.show', $assignment);
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        try {
            if ($assignment->submissions()->exists()) {
                return back()->with('error', 'Cannot delete an assignment that has submissions.');
            }
            $assignment->delete();
            session()->flash('success', 'Assignment archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.assignments.index');
    }

    public function publish(Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        $assignment->update(['status' => Assignment::STATUS_PUBLISHED]);

        session()->flash('success', 'Assignment published successfully.');

        return back();
    }

    public function close(Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        $assignment->update(['status' => Assignment::STATUS_CLOSED]);

        session()->flash('success', 'Assignment closed successfully.');

        return back();
    }
}
