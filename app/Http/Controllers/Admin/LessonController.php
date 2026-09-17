<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Lesson;
use App\Models\Module;
use App\Services\ReorderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function __construct(private ReorderService $reorder) {}

    public function index(Module $module, Request $request): View
    {
        $this->authorize('viewAny', Lesson::class);
        $this->authorize('view', $module);

        $query = Lesson::where('module_id', $module->id)->with('materials');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $lessons = $query->orderBy('position')->orderBy('id')->paginate(20);

        return view('admin.lessons.index', compact('module', 'lessons'));
    }

    public function create(Module $module): View
    {
        $this->authorize('create', Lesson::class);
        $this->authorize('view', $module);

        $modules = Module::where('course_id', $module->course_id)
            ->orderBy('position')
            ->get(['id', 'title']);

        return view('admin.lessons.create', compact('module', 'modules'));
    }

    public function store(StoreLessonRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['position'] = $data['position'] ?? Lesson::where('module_id', $data['module_id'])->max('position') + 1;
        $data['is_required'] = $request->boolean('is_required', false);

        $lesson = Lesson::create($data);

        session()->flash('success', 'Lesson created successfully.');

        return redirect()->route('admin.modules.lessons.show', [$lesson->module, $lesson]);
    }

    public function show(Module $module, Lesson $lesson): View
    {
        $this->authorize('view', $lesson);

        $lesson->load(['module.course', 'materials', 'creator']);

        return view('admin.lessons.show', compact('module', 'lesson'));
    }

    public function edit(Module $module, Lesson $lesson): View
    {
        $this->authorize('update', $lesson);

        $modules = Module::where('course_id', $module->course_id)
            ->orderBy('position')
            ->get(['id', 'title']);

        return view('admin.lessons.edit', compact('module', 'lesson', 'modules'));
    }

    public function update(UpdateLessonRequest $request, Module $module, Lesson $lesson): RedirectResponse
    {
        $data = $request->validated();
        $data['is_required'] = $request->boolean('is_required', $lesson->is_required);

        $lesson->update($data);

        session()->flash('success', 'Lesson updated successfully.');

        return redirect()->route('admin.modules.lessons.show', [$lesson->module, $lesson]);
    }

    public function destroy(Module $module, Lesson $lesson): RedirectResponse
    {
        $this->authorize('delete', $lesson);

        try {
            $lesson->delete();
            session()->flash('success', 'Lesson archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.modules.show', $module);
    }

    public function reorder(Request $request, Module $module): RedirectResponse
    {
        $request->validate([
            'lesson_ids' => 'required|array',
            'lesson_ids.*' => 'exists:lessons,id',
        ]);

        $this->authorize('update', $module);

        foreach ($request->lesson_ids as $id) {
            $lesson = Lesson::findOrFail($id);
            if ($lesson->module_id !== $module->id) {
                abort(403);
            }
            $this->authorize('update', $lesson);
        }

        $this->reorder->reorder(Lesson::class, $request->lesson_ids, 'module_id', $module->id);

        session()->flash('success', 'Lessons reordered successfully.');

        return back();
    }

    public function publish(Module $module, Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $lesson);

        $lesson->update(['status' => Lesson::STATUS_PUBLISHED]);

        session()->flash('success', 'Lesson published successfully.');

        return back();
    }

    public function unpublish(Module $module, Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $lesson);

        $lesson->update(['status' => Lesson::STATUS_DRAFT]);

        session()->flash('success', 'Lesson unpublished (draft) successfully.');

        return back();
    }
}
