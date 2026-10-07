<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Announcement::class);

        $query = Announcement::with(['course', 'class', 'targetRole', 'creator']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('audience_type')) {
            $query->where('audience_type', $request->audience_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $announcements = $query->orderByRaw('is_pinned DESC, created_at DESC')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return view('admin.announcements.index', compact('announcements', 'courses', 'classes', 'roles'));
    }

    public function create(): View
    {
        $this->authorize('create', Announcement::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return view('admin.announcements.create', compact('courses', 'classes', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Announcement::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'audience_type' => 'required|in:institution,course,class,role,users',
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'target_role_id' => 'nullable|exists:roles,id',
            'attachment_media_id' => 'nullable|exists:media_files,id',
            'is_pinned' => 'boolean',
            'publish_at' => 'nullable|date',
            'unpin_at' => 'nullable|date',
            'status' => 'required|in:draft,scheduled,published,archived',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['is_pinned'] = $request->boolean('is_pinned', false);

        $announcement = Announcement::create($validated);

        session()->flash('success', 'Announcement created successfully.');

        return redirect()->route('admin.announcements.show', $announcement);
    }

    public function show(Announcement $announcement): View
    {
        $this->authorize('view', $announcement);

        $announcement->load(['course', 'class', 'targetRole', 'attachment', 'creator', 'views.user']);

        return view('admin.announcements.show', compact('announcement'));
    }

    public function edit(Announcement $announcement): View
    {
        $this->authorize('update', $announcement);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return view('admin.announcements.edit', compact('announcement', 'courses', 'classes', 'roles'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'audience_type' => 'required|in:institution,course,class,role,users',
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'target_role_id' => 'nullable|exists:roles,id',
            'attachment_media_id' => 'nullable|exists:media_files,id',
            'is_pinned' => 'boolean',
            'publish_at' => 'nullable|date',
            'unpin_at' => 'nullable|date',
            'status' => 'required|in:draft,scheduled,published,archived',
        ]);

        $validated['is_pinned'] = $request->boolean('is_pinned', $announcement->is_pinned);

        $announcement->update($validated);

        session()->flash('success', 'Announcement updated successfully.');

        return redirect()->route('admin.announcements.show', $announcement);
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);

        try {
            $announcement->delete();
            session()->flash('success', 'Announcement archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.announcements.index');
    }

    public function pin(Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $announcement->update(['is_pinned' => ! $announcement->is_pinned]);

        session()->flash('success', $announcement->is_pinned ? 'Announcement pinned successfully.' : 'Announcement unpinned successfully.');

        return back();
    }

    public function publish(Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $announcement->update(['status' => Announcement::STATUS_PUBLISHED]);

        session()->flash('success', 'Announcement published successfully.');

        return back();
    }
}
