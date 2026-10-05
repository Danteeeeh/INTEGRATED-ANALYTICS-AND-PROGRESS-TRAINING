<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
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

        $modulesCount = $course->modules()->published()->count();

        $assignments = Assignment::published()
            ->where(function ($q) use ($course) {
                $q->whereHas('class', function ($q2) use ($course) {
                    $q2->where('course_id', $course->id);
                })
                ->orWhere(function ($q2) use ($course) {
                    $q2->whereHas('module', function ($q3) use ($course) {
                        $q3->where('course_id', $course->id)->published();
                    });
                })
                ->orWhere(function ($q2) use ($course) {
                    $q2->whereHas('lesson.module', function ($q3) use ($course) {
                        $q3->where('course_id', $course->id)->published();
                    });
                });
            });
        $assignmentsCount = (clone $assignments)->count();

        $quizzes = Quiz::published()
            ->where(function ($q) use ($course) {
                $q->whereHas('class', function ($q2) use ($course) {
                    $q2->where('course_id', $course->id);
                })
                ->orWhere(function ($q2) use ($course) {
                    $q2->whereHas('module', function ($q3) use ($course) {
                        $q3->where('course_id', $course->id)->published();
                    });
                })
                ->orWhere(function ($q2) use ($course) {
                    $q2->whereHas('lesson.module', function ($q3) use ($course) {
                        $q3->where('course_id', $course->id)->published();
                    });
                });
            });
        $quizzesCount = (clone $quizzes)->count();

        $exams = Exam::published()
            ->where(function ($q) use ($course) {
                $q->whereHas('class', function ($q2) use ($course) {
                    $q2->where('course_id', $course->id);
                })
                ->orWhere('exams.course_id', $course->id)
                ->orWhere(function ($q2) use ($course) {
                    $q2->whereHas('module', function ($q3) use ($course) {
                        $q3->where('course_id', $course->id)->published();
                    });
                });
            });
        $examsCount = (clone $exams)->count();

        $announcementsCount = $course->announcements()->count();

        $publishedModuleIds = $course->modules()->published()->pluck('id');
        $lessonIds = Lesson::whereIn('module_id', $publishedModuleIds)->published()->pluck('id');
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

        // Progress: exams attempted to completion (submitted / graded).
        $examsDone = $examsCount > 0
            ? ExamAttempt::whereIn('exam_id', (clone $exams)->pluck('exams.id'))
                ->where('student_id', $student->id)
                ->whereIn('status', [
                    ExamAttempt::STATUS_SUBMITTED,
                    ExamAttempt::STATUS_AUTO_SUBMITTED,
                    ExamAttempt::STATUS_GRADED,
                ])
                ->distinct('exam_id')
                ->count('exam_id')
            : 0;
        $examsProgress = $examsCount > 0 ? (int) round($examsDone / $examsCount * 100) : 0;

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
            'examsCount',
            'announcementsCount',
            'modulesProgress',
            'assignmentsProgress',
            'quizzesProgress',
            'examsProgress',
            'recentAnnouncements'
        ));
    }
}
