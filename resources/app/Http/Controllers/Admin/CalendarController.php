<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\ClassModel;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', CalendarEvent::class);

        $query = CalendarEvent::with(['user', 'course', 'class', 'creator', 'subject']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        if ($request->filled('start_date')) {
            $query->where('start_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('end_at', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $calendarEvents = $query->orderBy('start_at')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.calendar.index', compact('calendarEvents', 'courses', 'classes'));
    }

    public function create(): View
    {
        $this->authorize('create', CalendarEvent::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.calendar.create', compact('courses', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CalendarEvent::class);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:assignment,quiz,virtual_class,exam,announcement,course,personal',
            'related_type' => 'nullable|string|max:255',
            'related_id' => 'nullable|integer',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after_or_equal:start_at',
            'is_all_day' => 'boolean',
            'location' => 'nullable|string|max:255',
            'recurrence' => 'nullable|array',
            'visibility' => 'required|in:private,course,class,public',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['user_id'] = $validated['user_id'] ?? $request->user()->id;
        $validated['is_all_day'] = $request->boolean('is_all_day', false);

        $calendarEvent = CalendarEvent::create($validated);

        session()->flash('success', 'Calendar event created successfully.');

        return redirect()->route('admin.calendar.show', $calendarEvent);
    }

    public function show(CalendarEvent $calendarEvent): View
    {
        $this->authorize('view', $calendarEvent);

        $calendarEvent->load(['user', 'course', 'class', 'creator', 'subject']);

        return view('admin.calendar.show', compact('calendarEvent'));
    }

    public function edit(CalendarEvent $calendarEvent): View
    {
        $this->authorize('update', $calendarEvent);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.calendar.edit', compact('calendarEvent', 'courses', 'classes'));
    }

    public function update(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $this->authorize('update', $calendarEvent);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:assignment,quiz,virtual_class,exam,announcement,course,personal',
            'related_type' => 'nullable|string|max:255',
            'related_id' => 'nullable|integer',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after_or_equal:start_at',
            'is_all_day' => 'boolean',
            'location' => 'nullable|string|max:255',
            'recurrence' => 'nullable|array',
            'visibility' => 'required|in:private,course,class,public',
        ]);

        $validated['is_all_day'] = $request->boolean('is_all_day', $calendarEvent->is_all_day);

        $calendarEvent->update($validated);

        session()->flash('success', 'Calendar event updated successfully.');

        return redirect()->route('admin.calendar.show', $calendarEvent);
    }

    public function destroy(CalendarEvent $calendarEvent): RedirectResponse
    {
        $this->authorize('delete', $calendarEvent);

        try {
            $calendarEvent->delete();
            session()->flash('success', 'Calendar event archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.calendar.index');
    }
}
