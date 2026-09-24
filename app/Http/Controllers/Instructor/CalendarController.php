<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\ClassModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(ClassModel $class): View
    {
        $this->authorize('viewAny', CalendarEvent::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $events = CalendarEvent::where('class_id', $class->id)
            ->orWhere(function ($q) use ($class) {
                $q->where('course_id', $class->course_id)
                    ->where(function ($q2) {
                        $q2->where('visibility', CalendarEvent::VISIBILITY_CLASS)
                            ->orWhere('visibility', CalendarEvent::VISIBILITY_COURSE)
                            ->orWhere(function ($q3) {
                                $q3->where('visibility', CalendarEvent::VISIBILITY_PRIVATE)
                                    ->where('created_by', auth()->id());
                            });
                    });
            })
            ->with(['course', 'class', 'subject'])
            ->orderBy('start_at', 'asc')
            ->paginate(20);

        return view('instructor.calendar.index', compact('class', 'events'));
    }

    public function create(ClassModel $class): View
    {
        $this->authorize('create', CalendarEvent::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $eventTypes = [
            CalendarEvent::EVENT_TYPE_ASSIGNMENT => 'Assignment',
            CalendarEvent::EVENT_TYPE_QUIZ => 'Quiz',
            CalendarEvent::EVENT_TYPE_VIRTUAL_CLASS => 'Virtual Class',
            CalendarEvent::EVENT_TYPE_EXAM => 'Exam',
            CalendarEvent::EVENT_TYPE_ANNOUNCEMENT => 'Announcement',
            CalendarEvent::EVENT_TYPE_COURSE => 'Course Event',
            CalendarEvent::EVENT_TYPE_PERSONAL => 'Personal',
        ];

        $visibilityOptions = [
            CalendarEvent::VISIBILITY_PRIVATE => 'Private',
            CalendarEvent::VISIBILITY_CLASS => 'Class Only',
            CalendarEvent::VISIBILITY_COURSE => 'Course',
            CalendarEvent::VISIBILITY_PUBLIC => 'Public',
        ];

        return view('instructor.calendar.create', compact('class', 'eventTypes', 'visibilityOptions'));
    }

    public function store(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('create', CalendarEvent::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|string|in:assignment,quiz,virtual_class,exam,announcement,course,personal',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after:start_at',
            'is_all_day' => 'boolean',
            'location' => 'nullable|string|max:255',
            'visibility' => 'required|string|in:private,course,class,public',
        ]);

        $validated['class_id'] = $class->id;
        $validated['course_id'] = $class->course_id;
        $validated['user_id'] = auth()->id();
        $validated['created_by'] = auth()->id();
        $validated['is_all_day'] = $validated['is_all_day'] ?? false;

        CalendarEvent::create($validated);

        return redirect()->route('instructor.classes.calendar.index', $class)
            ->with('success', 'Calendar event created successfully.');
    }
}
