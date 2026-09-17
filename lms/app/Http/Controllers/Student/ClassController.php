<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\LessonProgress;
use App\Models\ModuleProgress;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(): View
    {
        $studentId = auth()->id();

        // Get available classes (active, not full, student not already enrolled)
        $enrolledClassIds = Enrollment::where('student_id', $studentId)
            ->where('status', '!=', 'dropped')
            ->pluck('class_id')
            ->toArray();

        $availableClasses = ClassModel::active()
            ->whereNotIn('id', $enrolledClassIds)
            ->with('course', 'academicPeriod', 'instructor')
            ->orderBy('code')
            ->get()
            ->filter(function ($class) {
                // Filter out full classes
                $currentEnrollments = Enrollment::where('class_id', $class->id)
                    ->where('status', '!=', 'dropped')
                    ->count();

                return $currentEnrollments < $class->max_students;
            });

        // Get enrolled classes
        $enrolledClasses = ClassModel::whereIn('id', $enrolledClassIds)
            ->with('course', 'academicPeriod', 'instructor')
            ->orderBy('code')
            ->get();

        return view('student.classes.index', compact('availableClasses', 'enrolledClasses'));
    }

    public function show(ClassModel $class): View
    {
        $class->load('course', 'academicPeriod', 'instructor');

        // Check if student is enrolled
        $isEnrolled = Enrollment::where('student_id', auth()->id())
            ->where('class_id', $class->id)
            ->where('status', '!=', 'dropped')
            ->exists();

        // Check enrollment status if enrolled
        $enrollment = null;
        if ($isEnrolled) {
            $enrollment = Enrollment::where('student_id', auth()->id())
                ->where('class_id', $class->id)
                ->first();
        }

        // Check if class is full
        $currentEnrollments = Enrollment::where('class_id', $class->id)
            ->where('status', '!=', 'dropped')
            ->count();
        $isFull = $currentEnrollments >= $class->max_students;

        // Get progress data if enrolled
        $progress = [];
        $upcomingActivities = collect();
        $announcements = collect();

        if ($isEnrolled && $enrollment->status === 'active') {
            $studentId = auth()->id();

            // Calculate progress
            $totalModules = $class->course->modules()->count();
            $completedModules = ModuleProgress::where('student_id', $studentId)
                ->whereHas('module', function ($q) use ($class) {
                    $q->where('course_id', $class->course_id);
                })
                ->where('status', ModuleProgress::STATUS_COMPLETED)
                ->count();

            $totalLessons = $class->course->lessons()->count();
            $completedLessons = LessonProgress::where('student_id', $studentId)
                ->whereHas('lesson', function ($q) use ($class) {
                    $q->whereHas('module', function ($q) use ($class) {
                        $q->where('course_id', $class->course_id);
                    });
                })
                ->where('status', LessonProgress::STATUS_COMPLETED)
                ->count();

            $totalAssignments = $class->assignments()->count();
            $completedAssignments = AssignmentSubmission::where('student_id', $studentId)
                ->whereHas('assignment', function ($q) use ($class) {
                    $q->where('class_id', $class->id);
                })
                ->whereIn('status', ['submitted', 'graded', 'returned'])
                ->count();

            $totalQuizzes = $class->quizzes()->count();
            $completedQuizzes = QuizAttempt::where('student_id', $studentId)
                ->whereHas('quiz', function ($q) use ($class) {
                    $q->where('class_id', $class->id);
                })
                ->whereIn('status', [
                    QuizAttempt::STATUS_SUBMITTED,
                    QuizAttempt::STATUS_AUTO_SUBMITTED,
                    QuizAttempt::STATUS_GRADED,
                ])
                ->count();

            // Calculate current grade
            $grades = Grade::where('student_id', $studentId)
                ->whereHas('item', function ($q) use ($class) {
                    $q->where('class_id', $class->id)
                        ->where('is_released', true);
                })
                ->get();

            $currentGrade = $grades->isNotEmpty() ? $grades->avg('score_percent') : 0;

            // Calculate overall progress
            $overallProgress = 0;
            if ($totalModules > 0 || $totalLessons > 0 || $totalAssignments > 0 || $totalQuizzes > 0) {
                $moduleProgress = ($totalModules > 0) ? ($completedModules / $totalModules) * 100 : 0;
                $lessonProgress = ($totalLessons > 0) ? ($completedLessons / $totalLessons) * 100 : 0;
                $assignmentProgress = ($totalAssignments > 0) ? ($completedAssignments / $totalAssignments) * 100 : 0;
                $quizProgress = ($totalQuizzes > 0) ? ($completedQuizzes / $totalQuizzes) * 100 : 0;

                $overallProgress = ($moduleProgress + $lessonProgress + $assignmentProgress + $quizProgress) / 4;
            }

            $progress = [
                'overall' => $overallProgress,
                'modules_completed' => $completedModules,
                'lessons_completed' => $completedLessons,
                'assignments_completed' => $completedAssignments,
                'quizzes_completed' => $completedQuizzes,
                'current_grade' => $currentGrade,
            ];

            // Get upcoming activities
            $upcomingAssignments = $class->assignments()
                ->where('due_date', '>', now())
                ->orderBy('due_date')
                ->limit(3)
                ->get()
                ->map(function ($assignment) {
                    return [
                        'title' => $assignment->title,
                        'type' => 'Assignment',
                        'date' => $assignment->due_date->format('M d, Y g:i A'),
                        'url' => route('student.courses.assignments.show', [$class->course, $assignment]),
                    ];
                });

            $upcomingQuizzes = $class->quizzes()
                ->where('availability_from', '>', now())
                ->orderBy('availability_from')
                ->limit(3)
                ->get()
                ->map(function ($quiz) {
                    return [
                        'title' => $quiz->title,
                        'type' => 'Quiz',
                        'date' => $quiz->availability_from->format('M d, Y g:i A'),
                        'url' => route('student.courses.quizzes.show', [$class->course, $quiz]),
                    ];
                });

            $upcomingVirtualClasses = $class->virtualClasses()
                ->where('meeting_date', '>=', now()->startOfDay())
                ->orderBy('meeting_date')
                ->limit(3)
                ->get()
                ->map(function ($virtualClass) {
                    return [
                        'title' => $virtualClass->title,
                        'type' => 'Virtual Class',
                        'date' => $virtualClass->meeting_date->format('M d, Y').' '.$virtualClass->start_time,
                        'url' => route('student.classes.virtual_classes.show', [$class, $virtualClass]),
                    ];
                });

            $upcomingActivities = $upcomingAssignments->concat($upcomingQuizzes)->concat($upcomingVirtualClasses)
                ->sortBy('date')
                ->take(5);

            // Get recent announcements
            $announcements = $class->announcements()
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
        }

        return view('student.classes.show', compact('class', 'isEnrolled', 'enrollment', 'isFull', 'progress', 'upcomingActivities', 'announcements'));
    }

    public function enroll(Request $request, ClassModel $class)
    {
        $studentId = auth()->id();

        // Check if already enrolled
        $existing = Enrollment::where('student_id', $studentId)
            ->where('class_id', $class->id)
            ->where('status', '!=', 'dropped')
            ->first();

        if ($existing) {
            return back()->with('error', 'You are already enrolled in this class.');
        }

        // Check if class is active
        if (! $class->is_active) {
            return back()->with('error', 'This class is not currently accepting enrollments.');
        }

        // Check class capacity
        $currentEnrollments = Enrollment::where('class_id', $class->id)
            ->where('status', '!=', 'dropped')
            ->count();

        if ($currentEnrollments >= $class->max_students) {
            return back()->with('error', 'This class is already at full capacity.');
        }

        Enrollment::create([
            'student_id' => $studentId,
            'class_id' => $class->id,
            'status' => 'active',
        ]);

        return redirect()->route('student.classes.show', $class)
            ->with('status', 'Successfully enrolled in the class!');
    }

    public function drop(ClassModel $class)
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('class_id', $class->id)
            ->where('status', '!=', 'dropped')
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'You are not enrolled in this class.');
        }

        $enrollment->drop();

        return redirect()->route('student.classes.index')
            ->with('status', 'You have dropped the class.');
    }
}
