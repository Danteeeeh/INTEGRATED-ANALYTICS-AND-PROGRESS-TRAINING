<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\DiscussionSubscription;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    public function index(Course $course): View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        $discussions = Discussion::published()
            ->where(function ($q) use ($course) {
                $q->where('course_id', $course->id)
                    ->orWhereHas('class', function ($q2) use ($course) {
                        $q2->where('course_id', $course->id);
                    });
            })
            ->with('creator')
            ->withCount('posts')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('student.discussions.index', compact('course', 'discussions', 'enrollment'));
    }

    public function show(Course $course, Discussion $discussion): View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        $discussion->load(['creator', 'posts' => function ($q) {
            $q->approved()
                ->with('author', 'children.author')
                ->whereNull('parent_id')
                ->orderBy('is_pinned', 'desc')
                ->orderBy('created_at', 'asc');
        }]);

        $isSubscribed = DiscussionSubscription::where('discussion_id', $discussion->id)
            ->where('user_id', $studentId)
            ->exists();

        return view('student.discussions.show', compact('course', 'discussion', 'enrollment', 'isSubscribed'));
    }

    public function subscribe(Request $request, Course $course, Discussion $discussion): RedirectResponse
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        $existing = DiscussionSubscription::where('discussion_id', $discussion->id)
            ->where('user_id', $studentId)
            ->first();

        if ($existing) {
            $existing->delete();
            $message = 'Unsubscribed from discussion.';
        } else {
            DiscussionSubscription::create([
                'discussion_id' => $discussion->id,
                'user_id' => $studentId,
                'subscribed_at' => now(),
            ]);
            $message = 'Subscribed to discussion successfully!';
        }

        return redirect()->route('student.courses.discussions.show', [$course, $discussion])
            ->with('status', $message);
    }

    public function storePost(Request $request, Course $course, Discussion $discussion): RedirectResponse
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        if ($discussion->is_locked) {
            return back()->with('error', 'This discussion is locked.');
        }

        $validated = $request->validate([
            'body' => 'required|string|min:3',
            'parent_id' => 'nullable|exists:discussion_posts,id',
        ]);

        if ($validated['parent_id'] ?? null) {
            $parent = DiscussionPost::findOrFail($validated['parent_id']);
            if ($parent->discussion_id !== $discussion->id) {
                abort(403);
            }
        }

        DiscussionPost::create([
            'discussion_id' => $discussion->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'author_id' => $studentId,
            'body' => $validated['body'],
            'is_approved' => true,
        ]);

        return redirect()->route('student.courses.discussions.show', [$course, $discussion])
            ->with('status', 'Post added successfully!');
    }
}
