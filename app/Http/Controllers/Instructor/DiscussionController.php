<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Discussion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    public function index(Course $course): View
    {
        $this->authorize('viewAny', Discussion::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $discussions = Discussion::where('course_id', $course->id)
            ->orWhere(function ($q) use ($course) {
                $q->whereHas('class.course', fn ($q2) => $q2->where('id', $course->id));
            })
            ->with(['course', 'class', 'module', 'lesson', 'creator', 'posts'])
            ->orderByDesc('is_pinned')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('instructor.courses.discussions.index', compact('course', 'discussions'));
    }

    public function create(Course $course): View
    {
        $this->authorize('create', Discussion::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $classes = ClassModel::where('course_id', $course->id)
            ->where('instructor_id', auth()->id())
            ->get();
        $modules = $course->modules()->with('lessons')->get();
        $discussionTypes = [
            Discussion::TYPE_GENERAL => 'General',
            Discussion::TYPE_ACADEMIC => 'Academic',
            Discussion::TYPE_QA => 'Q&A',
            Discussion::TYPE_GRADED => 'Graded',
        ];

        return view('instructor.courses.discussions.create', compact('course', 'classes', 'modules', 'discussionTypes'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('create', Discussion::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'discussion_type' => 'required|string|in:general,academic,qa,graded',
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'status' => 'required|string|in:draft,published,archived',
        ]);

        if ($request->filled('class_id')) {
            $class = ClassModel::find($request->class_id);
            abort_if(! $class || $class->instructor_id !== auth()->id(), 403);
        }

        $validated['course_id'] = $course->id;
        $validated['created_by'] = auth()->id();
        $validated['is_pinned'] = $validated['is_pinned'] ?? false;
        $validated['is_locked'] = $validated['is_locked'] ?? false;

        Discussion::create($validated);

        return redirect()->route('instructor.courses.discussions.index', $course)
            ->with('success', 'Discussion created successfully.');
    }

    public function show(Course $course, Discussion $discussion): View
    {
        $this->authorize('view', $discussion);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $discussion->load(['course', 'class', 'module', 'lesson', 'creator', 'posts.creator']);

        return view('instructor.courses.discussions.show', compact('course', 'discussion'));
    }

    public function pin(Course $course, Discussion $discussion): RedirectResponse
    {
        $this->authorize('update', $discussion);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $discussion->update(['is_pinned' => ! $discussion->is_pinned]);

        $message = $discussion->is_pinned ? 'Discussion pinned successfully.' : 'Discussion unpinned successfully.';

        return redirect()->route('instructor.courses.discussions.index', $course)
            ->with('success', $message);
    }

    public function lock(Course $course, Discussion $discussion): RedirectResponse
    {
        $this->authorize('update', $discussion);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $discussion->update(['is_locked' => ! $discussion->is_locked]);

        $message = $discussion->is_locked ? 'Discussion locked successfully.' : 'Discussion unlocked successfully.';

        return redirect()->route('instructor.courses.discussions.index', $course)
            ->with('success', $message);
    }
}
