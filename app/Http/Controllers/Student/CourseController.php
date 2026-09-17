<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Services\CourseService;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(private CourseService $courses) {}

    public function index(): View
    {
        $this->authorize('viewAny', Course::class);

        $courses = $this->courses->getCoursesByStudent(request()->user());

        return view('student.courses.index', ['enrollments' => request()->user()->enrollments()->with('class.course', 'class.instructor')->active()->paginate(15)]);
    }

    public function show(Course $course): View
    {
        $this->authorize('view', $course);

        $student = request()->user();

        // Only count content in classes the student is actually enrolled in.
        $classIds = $student->enrolledClasses()
            ->where('course_id', $course->id)
            ->wherePivot('status', 'active')
            ->pluck('classes.id');

        $modulesCount = $course->modules()->count();

        $assignments = $course->assignments()->whereIn('assignments.class_id', $classIds);
        $assignmentsCount = (clone $assignments)->count();

        $quizzes = $course->quizzes()->whereIn('quizzes.class_id', $classIds);
        $quizzesCount = (clone $quizzes)->count();

        $discussionsCount = $course->discussions()->count();
        $announcementsCount = $course->announcements()->count();

        // Progress: lessons completed across the course's modules.
        $lessonIds = Lesson::whereIn('module_id', $course->modules()->pluck('id'))->pluck('id');
        $lessonsTotal = $lessonIds->count();
        $lessonsDone = $lessonsTotal > 0
            ? LessonProgress::whereIn('lesson_id', $lessonIds)
                ->where('student_id', $student->id)
                ->where('status', LessonProgress::STATUS_COMPLETED)
                ->count()
            : 0;
        $modulesProgress = $lessonsTotal > 0 ? (int) round($lessonsDone / $lessonsTotal * 100) : 0;

        // Progress: assignments submitted (submitted or later).
        $assignmentsDone = $assignmentsCount > 0
            ? AssignmentSubmission::whereIn('assignment_id', (clone $assignments)->pluck('assignments.id'))
                ->where('student_id', $student->id)
                ->whereIn('status', [
                    AssignmentSubmission::STATUS_SUBMITTED,
                    AssignmentSubmission::STATUS_GRADED,
                    AssignmentSubmission::STATUS_RETURNED,
                    AssignmentSubmission::STATUS_RESUBMITTED,
                ])
                ->count()
            : 0;
        $assignmentsProgress = $assignmentsCount > 0 ? (int) round($assignmentsDone / $assignmentsCount * 100) : 0;

        // Progress: quizzes attempted to completion (submitted / graded).
        $quizzesDone = $quizzesCount > 0
            ? QuizAttempt::whereIn('quiz_id', (clone $quizzes)->pluck('quizzes.id'))
                ->where('student_id', $student->id)
                ->whereIn('status', [
                    QuizAttempt::STATUS_SUBMITTED,
                    QuizAttempt::STATUS_AUTO_SUBMITTED,
                    QuizAttempt::STATUS_GRADED,
                ])
                ->distinct('quiz_id')
                ->count('quiz_id')
            : 0;
        $quizzesProgress = $quizzesCount > 0 ? (int) round($quizzesDone / $quizzesCount * 100) : 0;

        $recentAnnouncements = $course->announcements()
            ->latest('publish_at')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('student.courses.show', compact(
            'course',
            'modulesCount',
            'assignmentsCount',
            'quizzesCount',
            'discussionsCount',
            'announcementsCount',
            'modulesProgress',
            'assignmentsProgress',
            'quizzesProgress',
            'recentAnnouncements'
        ));
    }
}
