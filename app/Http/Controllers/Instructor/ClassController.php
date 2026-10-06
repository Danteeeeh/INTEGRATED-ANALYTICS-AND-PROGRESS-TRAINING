<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Services\ClassService;
use App\Services\GradeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function __construct(private ClassService $classes) {}

    public function index(Request $request): View
    {
        $this->authorize('index', ClassModel::class);

        $classes = $this->classes->getClassesByInstructor(request()->user(), $request->only(['status', 'academic_period_id']));

        return view('instructor.classes.index', compact('classes'));
    }

    public function show(ClassModel $class): View
    {
        $this->authorize('view', $class);

        $class->load('course', 'academicPeriod', 'enrollments.student', 'assignments', 'quizzes', 'calendarEvents');

        // Real class grades, same computation as the gradebook and analytics.
        $gradeSummaries = app(GradeService::class)->computeClassGradeSummaries($class->id);

        return view('instructor.classes.show', compact('class', 'gradeSummaries'));
    }

    public function roster(ClassModel $class): View
    {
        $this->authorize('viewRoster', $class);

        $class->load([
            'enrollments.student',
            'enrollments.student.enrollments',
        ]);

        $enrollments = $class->enrollments()
            ->with('student')
            ->orderBy('enrolled_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => $class->enrollments()->count(),
            'active' => $class->enrollments()->where('status', 'active')->count(),
            'completed' => $class->enrollments()->where('status', 'completed')->count(),
            'dropped' => $class->enrollments()->where('status', 'dropped')->count(),
            'pending' => $class->enrollments()->where('status', 'pending')->count(),
        ];

        return view('instructor.classes.roster', compact('class', 'enrollments', 'stats'));
    }
}
