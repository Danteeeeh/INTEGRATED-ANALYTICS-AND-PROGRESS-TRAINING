<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Rubric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RubricController extends Controller
{
    public function index(Course $course): View
    {
        $this->authorize('viewAny', Rubric::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $rubrics = Rubric::where('created_by', auth()->id())
            ->where(function ($q) use ($course) {
                $q->where('course_id', $course->id)
                    ->orWhereNull('course_id');
            })
            ->orWhere(function ($q) use ($course) {
                $q->where('course_id', $course->id)
                    ->where('is_shared', true);
            })
            ->with(['course', 'class', 'criteria.levels'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('instructor.courses.rubrics.index', compact('course', 'rubrics'));
    }

    public function create(Course $course): View
    {
        $this->authorize('create', Rubric::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $classes = ClassModel::where('course_id', $course->id)
            ->where('instructor_id', auth()->id())
            ->get();

        return view('instructor.courses.rubrics.create', compact('course', 'classes'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('create', Rubric::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'class_id' => 'nullable|exists:classes,id',
            'is_shared' => 'boolean',
            'status' => 'required|string|in:draft,active,archived',
        ]);

        if ($request->filled('class_id')) {
            $class = ClassModel::find($request->class_id);
            abort_if(! $class || $class->instructor_id !== auth()->id(), 403);
        }

        $validated['course_id'] = $course->id;
        $validated['created_by'] = auth()->id();
        $validated['is_shared'] = $validated['is_shared'] ?? false;

        Rubric::create($validated);

        return redirect()->route('instructor.courses.rubrics.index', $course)
            ->with('success', 'Rubric created successfully.');
    }

    public function show(Course $course, Rubric $rubric): View
    {
        $this->authorize('view', $rubric);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $rubric->load(['course', 'class', 'criteria.levels', 'assignments']);

        return view('instructor.courses.rubrics.show', compact('course', 'rubric'));
    }

    public function edit(Course $course, Rubric $rubric): View
    {
        $this->authorize('update', $rubric);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($rubric->created_by !== auth()->id(), 403);

        $classes = ClassModel::where('course_id', $course->id)
            ->where('instructor_id', auth()->id())
            ->get();

        return view('instructor.courses.rubrics.edit', compact('course', 'rubric', 'classes'));
    }

    public function update(Request $request, Course $course, Rubric $rubric): RedirectResponse
    {
        $this->authorize('update', $rubric);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($rubric->created_by !== auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'class_id' => 'nullable|exists:classes,id',
            'is_shared' => 'boolean',
            'status' => 'required|string|in:draft,active,archived',
        ]);

        if ($request->filled('class_id')) {
            $class = ClassModel::find($request->class_id);
            abort_if(! $class || $class->instructor_id !== auth()->id(), 403);
        }

        $validated['is_shared'] = $validated['is_shared'] ?? false;

        $rubric->update($validated);

        return redirect()->route('instructor.courses.rubrics.index', $course)
            ->with('success', 'Rubric updated successfully.');
    }

    public function destroy(Course $course, Rubric $rubric): RedirectResponse
    {
        $this->authorize('delete', $rubric);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($rubric->created_by !== auth()->id(), 403);

        $rubric->delete();

        return redirect()->route('instructor.courses.rubrics.index', $course)
            ->with('success', 'Rubric deleted successfully.');
    }
}
