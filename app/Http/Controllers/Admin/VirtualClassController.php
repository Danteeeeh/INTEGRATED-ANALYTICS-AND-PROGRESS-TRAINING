<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VirtualClassController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', VirtualClass::class);

        $query = VirtualClass::with(['course', 'class', 'instructor', 'creator']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }

        if ($request->filled('meeting_provider')) {
            $query->where('meeting_provider', $request->meeting_provider);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('meeting_id', 'like', "%{$search}%");
            });
        }

        $virtualClasses = $query->orderBy('meeting_date')->orderBy('start_time')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $instructors = User::whereHas('role', function ($q) {
            $q->where('slug', Role::INSTRUCTOR);
        })->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.virtual_classes.index', compact('virtualClasses', 'courses', 'classes', 'instructors'));
    }

    public function create(): View
    {
        $this->authorize('create', VirtualClass::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $instructors = User::whereHas('role', function ($q) {
            $q->where('slug', Role::INSTRUCTOR);
        })->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.virtual_classes.create', compact('courses', 'classes', 'instructors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', VirtualClass::class);

        $validated = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'instructor_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'meeting_provider' => 'required|in:zoom,google_meet,microsoft_teams,other',
            'meeting_url' => 'nullable|url|max:500',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:255',
            'recurrence' => 'nullable|array',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
        ]);

        $validated['created_by'] = $request->user()->id;

        $virtualClass = VirtualClass::create($validated);

        session()->flash('success', 'Virtual class created successfully.');

        return redirect()->route('admin.virtual_classes.show', $virtualClass);
    }

    public function show(VirtualClass $virtualClass): View
    {
        $this->authorize('view', $virtualClass);

        $virtualClass->load(['course', 'class', 'instructor', 'creator', 'attendees.student']);

        return view('admin.virtual_classes.show', compact('virtualClass'));
    }

    public function edit(VirtualClass $virtualClass): View
    {
        $this->authorize('update', $virtualClass);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $instructors = User::whereHas('role', function ($q) {
            $q->where('slug', Role::INSTRUCTOR);
        })->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.virtual_classes.edit', compact('virtualClass', 'courses', 'classes', 'instructors'));
    }

    public function update(Request $request, VirtualClass $virtualClass): RedirectResponse
    {
        $this->authorize('update', $virtualClass);

        $validated = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'instructor_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'meeting_provider' => 'required|in:zoom,google_meet,microsoft_teams,other',
            'meeting_url' => 'nullable|url|max:500',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:255',
            'recurrence' => 'nullable|array',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
        ]);

        $virtualClass->update($validated);

        session()->flash('success', 'Virtual class updated successfully.');

        return redirect()->route('admin.virtual_classes.show', $virtualClass);
    }

    public function destroy(VirtualClass $virtualClass): RedirectResponse
    {
        $this->authorize('delete', $virtualClass);

        try {
            if ($virtualClass->attendees()->exists()) {
                return back()->with('error', 'Cannot delete a virtual class that has attendees.');
            }
            $virtualClass->delete();
            session()->flash('success', 'Virtual class archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.virtual_classes.index');
    }
}
