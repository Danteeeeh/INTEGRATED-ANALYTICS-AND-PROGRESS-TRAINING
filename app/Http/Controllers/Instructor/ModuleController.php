<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(Course $course): View
    {
        $this->authorize('viewAny', Module::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $modules = Module::where('course_id', $course->id)
            ->with('course', 'lessons')
            ->orderBy('position', 'asc')
            ->paginate(15);

        return view('instructor.courses.modules.index', compact('course', 'modules'));
    }

    public function create(Course $course): View
    {
        $this->authorize('create', Module::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $modules = Module::where('course_id', $course->id)->get();

        return view('instructor.courses.modules.create', compact('course', 'modules'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('create', Module::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'position' => 'nullable|integer',
            'is_required' => 'boolean',
            'prerequisites' => 'nullable|string',
            'completion_requirements' => 'nullable|string',
            'status' => 'required|string|in:draft,published,archived',
        ]);

        $validated['course_id'] = $course->id;
        $validated['created_by'] = auth()->id();
        $validated['position'] = $validated['position'] ?? Module::where('course_id', $course->id)->max('position') + 1;

        Module::create($validated);

        return redirect()->route('instructor.courses.modules.index', $course)
            ->with('success', 'Module created successfully.');
    }

    public function show(Course $course, Module $module): View
    {
        $this->authorize('view', $module);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);

        $module->load('course', 'lessons.materials');

        return view('instructor.courses.modules.show', compact('course', 'module'));
    }

    public function edit(Course $course, Module $module): View
    {
        $this->authorize('update', $module);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);

        return view('instructor.courses.modules.edit', compact('course', 'module'));
    }

    public function update(Request $request, Course $course, Module $module): RedirectResponse
    {
        $this->authorize('update', $module);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'position' => 'nullable|integer',
            'is_required' => 'boolean',
            'prerequisites' => 'nullable|string',
            'completion_requirements' => 'nullable|string',
            'status' => 'required|string|in:draft,published,archived',
        ]);

        $module->update($validated);

        return redirect()->route('instructor.courses.modules.index', $course)
            ->with('success', 'Module updated successfully.');
    }

    public function destroy(Course $course, Module $module): RedirectResponse
    {
        $this->authorize('delete', $module);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);

        $module->delete();

        return redirect()->route('instructor.courses.modules.index', $course)
            ->with('success', 'Module deleted successfully.');
    }

    public function reorder(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', Module::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:modules,id',
        ]);

        foreach ($validated['order'] as $position => $moduleId) {
            Module::where('id', $moduleId)
                ->where('course_id', $course->id)
                ->update(['position' => $position + 1]);
        }

        return redirect()->route('instructor.courses.modules.index', $course)
            ->with('success', 'Modules reordered successfully.');
    }

    public function publish(Course $course, Module $module): RedirectResponse
    {
        $this->authorize('update', $module);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);

        $module->update(['status' => Module::STATUS_PUBLISHED]);

        return redirect()->route('instructor.courses.modules.index', $course)
            ->with('success', 'Module published successfully.');
    }

    public function unpublish(Course $course, Module $module): RedirectResponse
    {
        $this->authorize('update', $module);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($module->course_id !== $course->id, 404);

        $module->update(['status' => Module::STATUS_DRAFT]);

        return redirect()->route('instructor.courses.modules.index', $course)
            ->with('success', 'Module unpublished successfully.');
    }
}
