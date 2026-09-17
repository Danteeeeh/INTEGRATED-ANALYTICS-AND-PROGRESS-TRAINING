<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Services\AuditService;
use App\Services\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(
        private CourseService $courses,
        private AuditService $audit,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Course::class);

        $courses = $this->courses->getAllCourses(request()->only(['status', 'search', 'academic_period_id']));

        return view('admin.courses.index', compact('courses'));
    }

    public function create(): View
    {
        $this->authorize('create', Course::class);

        $academicPeriods = AcademicPeriod::query()->orderBy('start_date', 'desc')->get();
        $categories = CourseCategory::active()->orderBy('name')->get();

        return view('admin.courses.create', compact('academicPeriods', 'categories'));
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = $this->courses->createCourse($request->validated(), $request->user());

        $this->audit->log($request->user(), 'course.created', Course::class, $course->id, null, $course->toArray(), $request);

        return redirect()->route('admin.courses.index')
            ->with('status', 'Course created successfully.');
    }

    public function show(Course $course): View
    {
        $this->authorize('view', $course);

        $course->load('classes.academicPeriod', 'classes.instructor', 'academicPeriod', 'creator');

        return view('admin.courses.show', compact('course'));
    }

    public function edit(Course $course): View
    {
        $this->authorize('update', $course);

        $academicPeriods = AcademicPeriod::query()->orderBy('start_date', 'desc')->get();
        $categories = CourseCategory::active()->orderBy('name')->get();

        return view('admin.courses.edit', compact('course', 'academicPeriods', 'categories'));
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $old = $course->toArray();
        $updated = $this->courses->updateCourse($course->id, $request->validated());

        $this->audit->log($request->user(), 'course.updated', Course::class, $course->id, $old, $updated->toArray(), $request);

        return redirect()->route('admin.courses.index')
            ->with('status', 'Course updated successfully.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);

        try {
            $this->courses->deleteCourse($course->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->log(request()->user(), 'course.deleted', Course::class, $course->id, $course->toArray(), null, request());

        return redirect()->route('admin.courses.index')
            ->with('status', 'Course archived successfully.');
    }

    public function publish(Course $course): RedirectResponse
    {
        $this->authorize('update', $course);
        $course->update(['status' => 'published']);
        $this->audit->log(request()->user(), 'course.published', Course::class, $course->id, null, ['status' => 'published'], request());

        return back()->with('status', 'Course published successfully.');
    }

    public function unpublish(Course $course): RedirectResponse
    {
        $this->authorize('update', $course);
        $course->update(['status' => 'draft']);
        $this->audit->log(request()->user(), 'course.unpublished', Course::class, $course->id, null, ['status' => 'draft'], request());

        return back()->with('status', 'Course moved back to draft.');
    }

    public function archive(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);
        $course->update(['status' => 'archived']);
        $this->audit->log(request()->user(), 'course.archived', Course::class, $course->id, null, ['status' => 'archived'], request());

        return back()->with('status', 'Course archived successfully.');
    }

    public function duplicate(Course $course): RedirectResponse
    {
        $this->authorize('create', Course::class);

        $baseCode = Str::upper($course->code.'-COPY');
        $code = $baseCode;
        $suffix = 2;
        while (Course::withTrashed()->where('code', $code)->exists()) {
            $code = $baseCode.'-'.$suffix++;
        }

        $copy = $course->replicate();
        $copy->fill([
            'code' => $code,
            'title' => $course->title.' (Copy)',
            'status' => 'draft',
            'created_by' => request()->user()?->id,
        ]);
        $copy->save();

        $this->audit->log(request()->user(), 'course.duplicated', Course::class, $copy->id, null, ['source_course_id' => $course->id], request());

        return redirect()->route('admin.courses.edit', $copy)
            ->with('status', 'Course copy created as a draft.');
    }
}
