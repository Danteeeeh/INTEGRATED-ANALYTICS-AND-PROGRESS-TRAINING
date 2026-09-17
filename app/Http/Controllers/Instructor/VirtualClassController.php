<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\VirtualClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        return view('instructor.virtual_classes.index', compact('class', 'virtualClasses'));
    }

    public function create(ClassModel $class): View
    {
        $this->authorize('create', VirtualClass::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $providers = [
            VirtualClass::PROVIDER_ZOOM => 'Zoom',
            VirtualClass::PROVIDER_GOOGLE_MEET => 'Google Meet',
            VirtualClass::PROVIDER_MICROSOFT_TEAMS => 'Microsoft Teams',
            VirtualClass::PROVIDER_OTHER => 'Other',
        ];

        $statusOptions = [
            VirtualClass::STATUS_SCHEDULED => 'Scheduled',
            VirtualClass::STATUS_ONGOING => 'Ongoing',
            VirtualClass::STATUS_COMPLETED => 'Completed',
            VirtualClass::STATUS_CANCELLED => 'Cancelled',
        ];

        return view('instructor.virtual_classes.create', compact('class', 'providers', 'statusOptions'));
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
            'meeting_provider' => 'required|string|in:zoom,google_meet,microsoft_teams,other',
            'meeting_url' => 'nullable|url',
            'meeting_id' => 'nullable|string|max:255',
            'meeting_password' => 'nullable|string|max:255',
            'status' => 'required|string|in:scheduled,ongoing,completed,cancelled',
        ]);

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

        return view('instructor.virtual_classes.show', compact('class', 'virtualClass'));
    }

    public function start(ClassModel $class, VirtualClass $virtualClass): RedirectResponse
    {
        $this->authorize('update', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);

        $virtualClass->update(['status' => VirtualClass::STATUS_ONGOING]);

        return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
            ->with('success', 'Virtual class started successfully.');
    }
}
