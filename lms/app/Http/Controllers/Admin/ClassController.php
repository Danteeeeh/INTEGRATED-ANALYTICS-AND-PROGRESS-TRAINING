<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Models\AcademicPeriod;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function __construct(
        private ClassService $classes,
        private AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClassModel::class);

        $classes = $this->classes->getAllClasses($request->only(['status', 'search', 'course_id', 'academic_period_id']));
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);

        return view('admin.classes.index', compact('classes', 'courses'));
    }

    public function create(): View
    {
        $this->authorize('create', ClassModel::class);

        return view('admin.classes.create', $this->formData());
    }

    public function store(StoreClassRequest $request): RedirectResponse
    {
        $class = $this->classes->createClass($request->validated());

        $this->audit->log($request->user(), 'class.created', ClassModel::class, $class->id, null, $class->toArray(), $request);

        return redirect()->route('admin.classes.index')
            ->with('status', 'Class created successfully.');
    }

    public function show(ClassModel $class): View
    {
        $this->authorize('view', $class);

        $class->load('course', 'academicPeriod', 'instructor', 'enrollments.student');

        return view('admin.classes.show', compact('class'));
    }

    public function edit(ClassModel $class): View
    {
        $this->authorize('update', $class);

        return view('admin.classes.edit', array_merge($this->formData(), compact('class')));
    }

    public function update(UpdateClassRequest $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('update', $class);

        $old = $class->toArray();
        $updated = $this->classes->updateClass($class->id, $request->validated());

        $this->audit->log($request->user(), 'class.updated', ClassModel::class, $class->id, $old, $updated->toArray(), $request);

        return redirect()->route('admin.classes.index')
            ->with('status', 'Class updated successfully.');
    }

    public function destroy(ClassModel $class): RedirectResponse
    {
        $this->authorize('delete', $class);

        try {
            $this->classes->deleteClass($class->id);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->log(request()->user(), 'class.deleted', ClassModel::class, $class->id, $class->toArray(), null, request());

        return redirect()->route('admin.classes.index')
            ->with('status', 'Class archived successfully.');
    }

    public function archive(ClassModel $class): RedirectResponse
    {
        $this->authorize('delete', $class);
        $class->update(['status' => 'archived']);
        $this->audit->log(request()->user(), 'class.archived', ClassModel::class, $class->id, null, ['status' => 'archived'], request());

        return back()->with('status', 'Class archived successfully.');
    }

    protected function formData(): array
    {
        return [
            'courses' => Course::query()->whereIn('status', ['published', 'draft'])->orderBy('code')->get(),
            'periods' => AcademicPeriod::query()->orderBy('start_date', 'desc')->get(),
            'instructors' => User::query()
                ->whereHas('role', fn ($q) => $q->where('slug', Role::INSTRUCTOR))
                ->where('status', 'active')
                ->orderBy('last_name')
                ->get(),
        ];
    }
}
