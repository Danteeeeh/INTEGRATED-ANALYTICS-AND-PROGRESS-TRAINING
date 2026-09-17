<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassModel;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Course $course): View
    {
        $this->authorize('viewAny', Announcement::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $announcements = Announcement::where('created_by', auth()->id())
            ->where(function ($q) use ($course) {
                $q->where('course_id', $course->id)
                    ->orWhere(function ($q2) use ($course) {
                        $q2->whereHas('class.course', fn ($q3) => $q3->where('id', $course->id));
                    });
            })
            ->with(['course', 'class', 'attachment', 'views'])
            ->orderByDesc('is_pinned')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('instructor.courses.announcements.index', compact('course', 'announcements'));
    }

    public function create(Course $course): View
    {
        $this->authorize('create', Announcement::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $classes = ClassModel::where('course_id', $course->id)
            ->where('instructor_id', auth()->id())
            ->get();
        $audienceTypes = [
            Announcement::AUDIENCE_COURSE => 'Course',
            Announcement::AUDIENCE_CLASS => 'Class',
        ];

        return view('instructor.courses.announcements.create', compact('course', 'classes', 'audienceTypes'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('create', Announcement::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'audience_type' => 'required|string|in:course,class,institution,role,users',
            'class_id' => 'nullable|exists:classes,id|required_if:audience_type,class',
            'is_pinned' => 'boolean',
            'publish_at' => 'nullable|date',
            'unpin_at' => 'nullable|date|after:publish_at',
            'status' => 'required|string|in:draft,scheduled,published,archived',
        ]);

        if ($request->filled('class_id')) {
            $class = ClassModel::find($request->class_id);
            abort_if(! $class || $class->instructor_id !== auth()->id(), 403);
        }

        if ($validated['audience_type'] === 'class') {
            $validated['course_id'] = null;
        } else {
            $validated['course_id'] = $course->id;
            $validated['class_id'] = null;
        }

        $validated['created_by'] = auth()->id();
        $validated['is_pinned'] = $validated['is_pinned'] ?? false;

        Announcement::create($validated);

        return redirect()->route('instructor.courses.announcements.index', $course)
            ->with('success', 'Announcement created successfully.');
    }

    public function show(Course $course, Announcement $announcement): View
    {
        $this->authorize('view', $announcement);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $announcement->load(['course', 'class', 'attachment']);

        return view('instructor.courses.announcements.show', compact('course', 'announcement'));
    }

    public function edit(Course $course, Announcement $announcement): View
    {
        $this->authorize('update', $announcement);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($announcement->created_by !== auth()->id(), 403);

        $classes = ClassModel::where('course_id', $course->id)
            ->where('instructor_id', auth()->id())
            ->get();
        $audienceTypes = [
            Announcement::AUDIENCE_COURSE => 'Course',
            Announcement::AUDIENCE_CLASS => 'Class',
        ];

        return view('instructor.courses.announcements.edit', compact('course', 'announcement', 'classes', 'audienceTypes'));
    }

    public function update(Request $request, Course $course, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($announcement->created_by !== auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'audience_type' => 'required|string|in:course,class,institution,role,users',
            'class_id' => 'nullable|exists:classes,id|required_if:audience_type,class',
            'is_pinned' => 'boolean',
            'publish_at' => 'nullable|date',
            'unpin_at' => 'nullable|date|after:publish_at',
            'status' => 'required|string|in:draft,scheduled,published,archived',
        ]);

        if ($request->filled('class_id')) {
            $class = ClassModel::find($request->class_id);
            abort_if(! $class || $class->instructor_id !== auth()->id(), 403);
        }

        if ($validated['audience_type'] === 'class') {
            $validated['course_id'] = null;
        } else {
            $validated['course_id'] = $course->id;
            $validated['class_id'] = null;
        }

        $validated['is_pinned'] = $validated['is_pinned'] ?? false;

        $announcement->update($validated);

        return redirect()->route('instructor.courses.announcements.index', $course)
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Course $course, Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($announcement->created_by !== auth()->id(), 403);

        $announcement->delete();

        return redirect()->route('instructor.courses.announcements.index', $course)
            ->with('success', 'Announcement deleted successfully.');
    }

    public function pin(Course $course, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($announcement->created_by !== auth()->id(), 403);

        $announcement->update(['is_pinned' => ! $announcement->is_pinned]);

        $message = $announcement->is_pinned ? 'Announcement pinned successfully.' : 'Announcement unpinned successfully.';

        return redirect()->route('instructor.courses.announcements.index', $course)
            ->with('success', $message);
    }
}
