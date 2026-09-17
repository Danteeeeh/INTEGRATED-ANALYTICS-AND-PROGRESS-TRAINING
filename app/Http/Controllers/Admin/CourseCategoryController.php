<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseCategory;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseCategoryController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CourseCategory::class);

        $query = CourseCategory::with(['parent', 'creator']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $courseCategories = $query->orderBy('name')->paginate(15);

        return view('admin.course_categories.index', compact('courseCategories'));
    }

    public function create(): View
    {
        $this->authorize('create', CourseCategory::class);

        $parents = CourseCategory::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);

        return view('admin.course_categories.create', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CourseCategory::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:course_categories,code',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:course_categories,id',
            'thumbnail' => 'nullable|string|max:255',
            'status' => 'required|in:active,draft,archived',
        ]);

        $validated['created_by'] = $request->user()->id;

        $courseCategory = CourseCategory::create($validated);

        $this->audit->log($request->user(), 'course_category.created', CourseCategory::class, $courseCategory->id, null, $courseCategory->toArray(), $request);

        session()->flash('success', 'Course category created successfully.');

        return redirect()->route('admin.course_categories.show', $courseCategory);
    }

    public function show(CourseCategory $courseCategory): View
    {
        $this->authorize('view', $courseCategory);

        $courseCategory->load(['parent', 'children', 'courses', 'creator']);

        return view('admin.course_categories.show', compact('courseCategory'));
    }

    public function edit(CourseCategory $courseCategory): View
    {
        $this->authorize('update', $courseCategory);

        $parents = CourseCategory::whereNull('parent_id')
            ->where('id', '!=', $courseCategory->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.course_categories.edit', compact('courseCategory', 'parents'));
    }

    public function update(Request $request, CourseCategory $courseCategory): RedirectResponse
    {
        $this->authorize('update', $courseCategory);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:course_categories,code,'.$courseCategory->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:course_categories,id',
            'thumbnail' => 'nullable|string|max:255',
            'status' => 'required|in:active,draft,archived',
        ]);

        $old = $courseCategory->toArray();

        $courseCategory->update($validated);

        $this->audit->log($request->user(), 'course_category.updated', CourseCategory::class, $courseCategory->id, $old, $courseCategory->toArray(), $request);

        session()->flash('success', 'Course category updated successfully.');

        return redirect()->route('admin.course_categories.show', $courseCategory);
    }

    public function destroy(CourseCategory $courseCategory): RedirectResponse
    {
        $this->authorize('delete', $courseCategory);

        try {
            if ($courseCategory->children()->exists()) {
                return back()->with('error', 'Cannot delete a category that still has sub-categories.');
            }
            if ($courseCategory->courses()->exists()) {
                return back()->with('error', 'Cannot delete a category that still has courses.');
            }
            $old = $courseCategory->toArray();
            $courseCategory->delete();

            $this->audit->log(request()->user(), 'course_category.deleted', CourseCategory::class, $courseCategory->id, $old, null, request());

            session()->flash('success', 'Course category archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.course_categories.index');
    }
}
