<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Services\NotificationService;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

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
            'recurrence' => 'nullable|array',
            'status' => 'sometimes|in:scheduled,ongoing,completed,cancelled',
        ]);

        // Built-in Virtual Classroom (LiveKit) is the only workflow — no
        // external provider links for new classes. Legacy Zoom/Meet/Teams
        // records keep working untouched.
        $validated['meeting_provider'] = VirtualClass::PROVIDER_LIVEKIT;
        $validated['livekit_room_name'] = 'lms-class-'.($validated['class_id'] ?? 'course').'-'.Str::lower(Str::random(20));
        $validated['meeting_url'] = null;
        $validated['meeting_id'] = null;
        $validated['meeting_password'] = null;

        // Built-in form schedules by default; clamp any caller-supplied
        // status so a class cannot be created already-live.
        $validated['status'] = VirtualClass::STATUS_SCHEDULED;

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
            'recurrence' => 'nullable|array',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
        ]);

        // Built-in only for new/edited classes; keep the provider as-is when
        // editing a legacy external record, and never wipe its room name.
        if ($virtualClass->meeting_provider !== VirtualClass::PROVIDER_LIVEKIT) {
            $virtualClass->update($validated);

            session()->flash('success', 'Virtual class updated successfully.');

            return redirect()->route('admin.virtual_classes.show', $virtualClass);
        }

        $validated['meeting_provider'] = VirtualClass::PROVIDER_LIVEKIT;
        $validated['livekit_room_name'] = $virtualClass->livekit_room_name
            ?: 'lms-class-'.($validated['class_id'] ?? $virtualClass->class_id ?? 'course').'-'.Str::lower(Str::random(20));
        $validated['meeting_url'] = null;
        $validated['meeting_id'] = null;
        $validated['meeting_password'] = null;

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
