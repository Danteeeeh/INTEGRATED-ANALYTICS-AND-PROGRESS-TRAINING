<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Department;
use App\Models\Program;
use App\Services\AuditService;
use App\Services\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(
        private CourseService $courses,
        private AuditService $audit,
    ) {}

    public function index(): View
    {
        $courses = Course::withCount('classes')->orderBy('title')->paginate(15);

        return view('registrar.courses.index', compact('courses'));
    }

    public function create(): View
    {
        $academicPeriods = AcademicPeriod::query()->orderBy('start_date', 'desc')->get();
        $departments = Department::orderBy('name')->get();
        $programs = Program::with('department')->orderBy('code')->get();

        return view('registrar.courses.create', compact('academicPeriods', 'departments', 'programs'));
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = $this->courses->createCourse($request->validated(), $request->user());

        $this->audit->log($request->user(), 'course.created', Course::class, $course->id, null, $course->toArray(), $request);

        return redirect()->route('registrar.courses.index')
            ->with('status', 'Course created successfully.');
    }

    public function show(Course $course): View
    {
        $course->load('classes.instructor', 'classes.enrollments');

        return view('registrar.courses.show', compact('course'));
    }

    public function edit(Course $course): View
    {
        $academicPeriods = AcademicPeriod::query()->orderBy('start_date', 'desc')->get();
        $departments = Department::orderBy('name')->get();
        $programs = Program::with('department')->orderBy('code')->get();

        return view('registrar.courses.edit', compact('course', 'academicPeriods', 'departments', 'programs'));
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $old = $course->toArray();
        $updated = $this->courses->updateCourse($course->id, $request->validated());

        $this->audit->log($request->user(), 'course.updated', Course::class, $course->id, $old, $updated->toArray(), $request);

        return redirect()->route('registrar.courses.index')
            ->with('status', 'Course updated successfully.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        try {
            $this->courses->deleteCourse($course->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->log(request()->user(), 'course.deleted', Course::class, $course->id, $course->toArray(), null, request());

        return redirect()->route('registrar.courses.index')
            ->with('status', 'Course deleted successfully.');
    }
}
