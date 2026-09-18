<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Discussion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Discussion::class);

        $query = Discussion::with(['course', 'class', 'creator']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('type')) {
            $query->where('discussion_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $discussions = $query->orderByRaw('is_pinned DESC, created_at DESC')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.discussions.index', compact('discussions', 'courses', 'classes'));
    }

    public function create(): View
    {
        $this->authorize('create', Discussion::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.discussions.create', compact('courses', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Discussion::class);

        $validated = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discussion_type' => 'required|in:general,academic,qa,graded',
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'status' => 'required|in:draft,published,archived',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['is_pinned'] = $request->boolean('is_pinned', false);
        $validated['is_locked'] = $request->boolean('is_locked', false);

        $discussion = Discussion::create($validated);

        session()->flash('success', 'Discussion created successfully.');

        return redirect()->route('admin.discussions.show', $discussion);
    }

    public function show(Discussion $discussion): View
    {
        $this->authorize('view', $discussion);

        $discussion->load(['course', 'class', 'module', 'lesson', 'creator', 'posts.creator', 'rootPosts.creator', 'subscriptions']);

        return view('admin.discussions.show', compact('discussion'));
    }

    public function edit(Discussion $discussion): View
    {
        $this->authorize('update', $discussion);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.discussions.edit', compact('discussion', 'courses', 'classes'));
    }

    public function update(Request $request, Discussion $discussion): RedirectResponse
    {
        $this->authorize('update', $discussion);

        $validated = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discussion_type' => 'required|in:general,academic,qa,graded',
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'status' => 'required|in:draft,published,archived',
        ]);

        $validated['is_pinned'] = $request->boolean('is_pinned', $discussion->is_pinned);
        $validated['is_locked'] = $request->boolean('is_locked', $discussion->is_locked);

        $discussion->update($validated);

        session()->flash('success', 'Discussion updated successfully.');

        return redirect()->route('admin.discussions.show', $discussion);
    }

    public function destroy(Discussion $discussion): RedirectResponse
    {
        $this->authorize('delete', $discussion);

        try {
            $discussion->delete();
            session()->flash('success', 'Discussion archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.discussions.index');
    }

    public function pin(Discussion $discussion): RedirectResponse
    {
        $this->authorize('update', $discussion);

        $discussion->update(['is_pinned' => ! $discussion->is_pinned]);

        session()->flash('success', $discussion->is_pinned ? 'Discussion pinned successfully.' : 'Discussion unpinned successfully.');

        return back();
    }

    public function lock(Discussion $discussion): RedirectResponse
    {
        $this->authorize('update', $discussion);

        $discussion->update(['is_locked' => ! $discussion->is_locked]);

        session()->flash('success', $discussion->is_locked ? 'Discussion locked successfully.' : 'Discussion unlocked successfully.');

        return back();
    }
}
