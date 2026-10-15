<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\VirtualClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class VirtualClassController extends Controller
{
    public function index(ClassModel $class): View
    {
        $this->authorize('viewAny', VirtualClass::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $virtualClasses = VirtualClass::where('class_id', $class->id)
            ->with(['course', 'class', 'instructor', 'attendees'])
            ->orderBy('meeting_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->paginate(15);

        // Meetings whose end time has passed close themselves on view.
        $virtualClasses->getCollection()->each->syncStatus();

        return view('instructor.virtual_classes.index', compact('class', 'virtualClasses'));
    }

    public function create(ClassModel $class): View
    {
        $this->authorize('create', VirtualClass::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        // Built-in Virtual Classroom (LiveKit) is the only workflow — the
        // provider is forced on the server at store() time, so the form only
        // edits the schedule. No legacy provider/status pickers here.
        return view('instructor.virtual_classes.create', compact('class'));
    }

    public function store(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('create', VirtualClass::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'sometimes|string|in:scheduled,ongoing,completed,cancelled',
        ]);

        // The built-in form always schedules; any caller-supplied status is
        // clamped to scheduled so a class cannot be created already-live.
        $validated['status'] = VirtualClass::STATUS_SCHEDULED;

        // Built-in Virtual Classroom (LiveKit) is the only workflow — the
        // instructor hosts from inside the LMS and students join from their
        // course page. No external provider links for new classes.
        $validated['meeting_provider'] = VirtualClass::PROVIDER_LIVEKIT;
        $validated['livekit_room_name'] = 'lms-class-'.$class->id.'-'.Str::lower(Str::random(20));
        $validated['meeting_url'] = null;
        $validated['meeting_id'] = null;
        $validated['meeting_password'] = null;

        $validated['course_id'] = $class->course_id;
        $validated['class_id'] = $class->id;
        $validated['instructor_id'] = auth()->id();
        $validated['created_by'] = auth()->id();

        VirtualClass::create($validated);

        return redirect()->route('instructor.classes.virtual_classes.index', $class)
            ->with('success', 'Virtual class created successfully.');
    }

    public function show(ClassModel $class, VirtualClass $virtualClass): View
    {
        $this->authorize('view', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);

        $virtualClass->load(['course', 'class', 'instructor', 'attendees.student']);

        // Auto-close the meeting once its scheduled end time has passed.
        $virtualClass->syncStatus();

        return view('instructor.virtual_classes.show', compact('class', 'virtualClass'));
    }

    public function edit(ClassModel $class, VirtualClass $virtualClass): View
    {
        $this->authorize('update', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);

        // Only an upcoming (scheduled) class can be edited or rescheduled.
        abort_unless($virtualClass->status === VirtualClass::STATUS_SCHEDULED, 403, 'Only scheduled virtual classes can be edited.');

        return view('instructor.virtual_classes.edit', compact('class', 'virtualClass'));
    }

    public function update(Request $request, ClassModel $class, VirtualClass $virtualClass): RedirectResponse
    {
        $this->authorize('update', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);
        abort_unless($virtualClass->status === VirtualClass::STATUS_SCHEDULED, 403, 'Only scheduled virtual classes can be updated.');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        // Built-in Virtual Classroom only: keep the provider and the generated
        // room name untouched (never let the client re-point the room).
        $validated['meeting_provider'] = $virtualClass->meeting_provider ?: VirtualClass::PROVIDER_LIVEKIT;

        $virtualClass->update($validated);

        return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
            ->with('success', 'Virtual class updated successfully.');
    }

    public function cancel(ClassModel $class, VirtualClass $virtualClass): RedirectResponse
    {
        $this->authorize('update', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);

        if (! $virtualClass->canCancel()) {
            return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
                ->with('error', 'This virtual class cannot be cancelled in its current state.');
        }

        $virtualClass->update(['status' => VirtualClass::STATUS_CANCELLED]);

        return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
            ->with('success', 'Virtual class cancelled.');
    }

    public function start(ClassModel $class, VirtualClass $virtualClass): RedirectResponse
    {
        $this->authorize('update', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);

        // A class can only be started from Scheduled and before its end time
        // — a completed or cancelled class cannot be force-reopened.
        abort_unless($virtualClass->canStart(), 403, 'This virtual class cannot be started in its current state.');

        $virtualClass->update(['status' => VirtualClass::STATUS_ONGOING]);

        return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
            ->with('success', 'Virtual class started successfully.');
    }

    /**
     * Manually close the meeting (the instructor-driven equivalent of the
     * automatic close that fires once the end time passes). Only a live
     * (ongoing) class can be closed this way.
     */
    public function end(ClassModel $class, VirtualClass $virtualClass): RedirectResponse
    {
        $this->authorize('update', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);

        if (! $virtualClass->canEnd()) {
            return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
                ->with('error', 'This virtual class is already closed.');
        }

        $virtualClass->update(['status' => VirtualClass::STATUS_COMPLETED]);

        return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
            ->with('success', 'Virtual class ended and closed.');
    }
}
