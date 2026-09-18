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

class RubricController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Rubric::class);

        $query = Rubric::with(['course', 'class', 'creator']);

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
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $rubrics = $query->orderByDesc('created_at')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.rubrics.index', compact('rubrics', 'courses', 'classes'));
    }

    public function create(): View
    {
        $this->authorize('create', Rubric::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.rubrics.create', compact('courses', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Rubric::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'is_shared' => 'boolean',
            'status' => 'required|in:draft,active,archived',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['is_shared'] = $request->boolean('is_shared', false);

        $rubric = Rubric::create($validated);

        session()->flash('success', 'Rubric created successfully.');

        return redirect()->route('admin.rubrics.show', $rubric);
    }

    public function show(Rubric $rubric): View
    {
        $this->authorize('view', $rubric);

        $rubric->load(['course', 'class', 'creator', 'criteria.levels', 'assignments.class.course']);

        return view('admin.rubrics.show', compact('rubric'));
    }

    public function edit(Rubric $rubric): View
    {
        $this->authorize('update', $rubric);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.rubrics.edit', compact('rubric', 'courses', 'classes'));
    }

    public function update(Request $request, Rubric $rubric): RedirectResponse
    {
        $this->authorize('update', $rubric);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'is_shared' => 'boolean',
            'status' => 'required|in:draft,active,archived',
        ]);

        $validated['is_shared'] = $request->boolean('is_shared', $rubric->is_shared);

        $rubric->update($validated);

        session()->flash('success', 'Rubric updated successfully.');

        return redirect()->route('admin.rubrics.show', $rubric);
    }

    public function destroy(Rubric $rubric): RedirectResponse
    {
        $this->authorize('delete', $rubric);

        try {
            if ($rubric->assignments()->exists()) {
                return back()->with('error', 'Cannot delete a rubric that is attached to assignments.');
            }
            if ($rubric->assessments()->exists()) {
                return back()->with('error', 'Cannot delete a rubric that has assessments.');
            }
            $rubric->delete();
            session()->flash('success', 'Rubric archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.rubrics.index');
    }

    public function attach(Request $request, Rubric $rubric): RedirectResponse
    {
        $this->authorize('update', $rubric);

        $validated = $request->validate([
            'assignment_ids' => 'required|array',
            'assignment_ids.*' => 'exists:assignments,id',
        ]);

        $attached = 0;
        foreach ($validated['assignment_ids'] as $assignmentId) {
            $assignment = Assignment::findOrFail($assignmentId);
            $assignment->update(['rubric_id' => $rubric->id]);
            $attached++;
        }

        session()->flash('success', "Rubric attached to {$attached} assignment(s) successfully.");

        return back();
    }
}
