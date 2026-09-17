<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(private CourseService $courses) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Course::class);

        $courses = $this->courses->getCoursesByInstructor(request()->user(), $request->only(['status', 'academic_period_id']));

        return view('instructor.courses.index', compact('courses'));
    }

    public function show(Course $course): View
    {
        $this->authorize('view', $course);

        $course->load([
            'classes.academicPeriod',
            'classes.instructor',
            'academicPeriod',
            'modules' => fn ($q) => $q->orderBy('position'),
            'modules.lessons',
            'discussions',
            'announcements',
        ]);

        // Assignments & quizzes live on classes (class_id), not courses directly.
        $assignments = $course->classes->flatMap->assignments;
        $quizzes = $course->classes->flatMap->quizzes;

        return view('instructor.courses.show', compact('course', 'assignments', 'quizzes'));
    }

    public function edit(Course $course): View
    {
        $this->authorize('update', $course);

        $academicPeriods = AcademicPeriod::query()->orderBy('start_date', 'desc')->get();

        return view('instructor.courses.edit', compact('course', 'academicPeriods'));
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $this->courses->updateCourse($course->id, $request->validated());

        return redirect()->route('instructor.courses.index')
            ->with('status', 'Course updated successfully.');
    }
}
