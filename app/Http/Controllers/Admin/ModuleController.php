<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModuleRequest;
use App\Http\Requests\UpdateModuleRequest;
use App\Models\Course;
use App\Models\Module;
use App\Services\ReorderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function __construct(private ReorderService $reorder) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Module::class);

        $query = Module::with(['course', 'lessons']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
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

        $modules = $query->orderBy('course_id')->orderBy('position')->orderBy('id')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);

        return view('admin.modules.index', compact('modules', 'courses'));
    }

    public function create(): View
    {
        $this->authorize('create', Module::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);

        return view('admin.modules.create', compact('courses'));
    }

    public function store(StoreModuleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['position'] = $data['position'] ?? Module::where('course_id', $data['course_id'])->max('position') + 1;
        $data['is_required'] = $request->boolean('is_required', false);

        $module = Module::create($data);

        session()->flash('success', 'Module created successfully.');

        return redirect()->route('admin.modules.show', $module);
    }

    public function show(Module $module): View
    {
        $this->authorize('view', $module);

        $module->load(['course', 'lessons.materials', 'creator']);

        return view('admin.modules.show', compact('module'));
    }

    public function edit(Module $module): View
    {
        $this->authorize('update', $module);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);

        return view('admin.modules.edit', compact('module', 'courses'));
    }

    public function update(UpdateModuleRequest $request, Module $module): RedirectResponse
    {
        $data = $request->validated();
        $data['is_required'] = $request->boolean('is_required', $module->is_required);

        $module->update($data);

        session()->flash('success', 'Module updated successfully.');

        return redirect()->route('admin.modules.show', $module);
    }

    public function destroy(Module $module): RedirectResponse
    {
        $this->authorize('delete', $module);

        try {
            if ($module->lessons()->exists()) {
                return back()->with('error', 'Cannot delete a module that still has lessons.');
            }
            $module->delete();
            session()->flash('success', 'Module archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.modules.index');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'module_ids' => 'required|array',
            'module_ids.*' => 'exists:modules,id',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        $this->authorize('reorder', Module::class);

        foreach ($request->module_ids as $id) {
            $module = Module::findOrFail($id);
            $this->authorize('update', $module);
        }

        $this->reorder->reorder(
            Module::class,
            $request->module_ids,
            $request->filled('course_id') ? 'course_id' : null,
            $request->course_id
        );

        session()->flash('success', 'Modules reordered successfully.');

        return back();
    }

    public function publish(Module $module): RedirectResponse
    {
        $this->authorize('publish', $module);

        $module->update(['status' => Module::STATUS_PUBLISHED]);

        session()->flash('success', 'Module published successfully.');

        return back();
    }

    public function unpublish(Module $module): RedirectResponse
    {
        $this->authorize('publish', $module);

        $module->update(['status' => Module::STATUS_DRAFT]);

        session()->flash('success', 'Module unpublished (draft) successfully.');

        return back();
    }
}
