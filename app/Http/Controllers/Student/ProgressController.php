<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CourseCompletion;
use App\Models\CourseProgress;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\LessonProgress;
use App\Models\ModuleProgress;
use App\Models\QuizAttempt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function __invoke(): View
    {
        $studentId = auth()->id();

        // Get student's enrollments
        $enrollments = Enrollment::where('student_id', $studentId)
            ->with(['class.course', 'class.instructor', 'class.academicPeriod'])
            ->orderBy('created_at', 'desc')
            ->get();

        $activeEnrollments = $enrollments->where('status', 'active');
        $completedEnrollments = $enrollments->where('status', 'completed');

        // Course progress data
        $courseProgressData = [];
        foreach ($activeEnrollments as $enrollment) {
            $course = $enrollment->class->course;
            if ($course) {
                $progress = CourseProgress::where('student_id', $studentId)
                    ->where('class_id', $enrollment->class_id)
                    ->first();

                $moduleProgress = ModuleProgress::where('student_id', $studentId)
                    ->where('class_id', $enrollment->class_id)
                    ->with('module')
                    ->get();

                $lessonProgress = LessonProgress::where('student_id', $studentId)
                    ->whereHas('lesson.module', function ($query) use ($course) {
                        $query->where('course_id', $course->id);
                    })
                    ->get();

                $courseProgressData[] = [
                    'course' => $course,
                    'class' => $enrollment->class,
                    'progress' => $progress ? $progress->progress_percent : 0,
                    'last_accessed' => $progress ? $progress->updated_at : null,
                    'modules_completed' => $moduleProgress->where('status', 'completed')->count(),
                    'total_modules' => $moduleProgress->count(),
                    'lessons_completed' => $lessonProgress->where('status', 'completed')->count(),
                    'total_lessons' => $lessonProgress->count(),
                ];
            }
        }

        // Overall statistics
        $totalCourses = $activeEnrollments->count();
        $overallProgress = $totalCourses > 0 
            ? collect($courseProgressData)->avg('progress') 
            : 0;

        // Assignment progress
        $classIds = $activeEnrollments->pluck('class_id');
        $totalAssignments = Assignment::whereIn('class_id', $classIds)->count();
        $submittedAssignments = AssignmentSubmission::where('student_id', $studentId)
            ->whereIn('class_id', $classIds)
            ->count();
        $assignmentProgress = $totalAssignments > 0 
            ? ($submittedAssignments / $totalAssignments) * 100 
            : 0;

        // Quiz progress
        $totalQuizzes = \App\Models\Quiz::whereIn('class_id', $classIds)->count();
        $quizAttempts = QuizAttempt::where('student_id', $studentId)
            ->whereInHas('quiz', function ($query) use ($classIds) {
                $query->whereIn('class_id', $classIds);
            })
            ->count();
        $quizProgress = $totalQuizzes > 0 
            ? ($quizAttempts / $totalQuizzes) * 100 
            : 0;

        // Grades progress
        $recentGrades = Grade::where('student_id', $studentId)
            ->with('item.class.course')
            ->whereHas('item', fn ($q) => $q->where('is_released', true))
            ->orderBy('graded_at', 'desc')
            ->limit(10)
            ->get();

        $averageGrade = $recentGrades->isNotEmpty() 
            ? $recentGrades->avg('score_percent') 
            : 0;

        // Learning streak
        $learningStreak = $this->calculateLearningStreak($studentId);

        // Course completions
        $completions = CourseCompletion::where('student_id', $studentId)
            ->with('class.course')
            ->orderBy('completed_at', 'desc')
            ->get();

        // Weekly activity
        $weeklyActivity = $this->getWeeklyActivity($studentId);

        // Completion timeline
        $completionTimeline = $this->getCompletionTimeline($studentId);

        return view('student.progress', compact(
            'courseProgressData',
            'overallProgress',
            'totalCourses',
            'assignmentProgress',
            'quizProgress',
            'recentGrades',
            'averageGrade',
            'learningStreak',
            'completions',
            'weeklyActivity',
            'completionTimeline',
            'activeEnrollments',
            'completedEnrollments'
        ));
    }

    protected function calculateLearningStreak(int $studentId): int
    {
        $today = Carbon::today();
        $streak = 0;

        for ($i = 0; $i < 30; $i++) {
            $date = $today->copy()->subDays($i);
            $hasActivity = LessonProgress::where('student_id', $studentId)
                ->whereDate('updated_at', $date)
                ->exists();

            if ($hasActivity) {
                $streak++;
            } elseif ($i > 0) {
                break;
            }
        }

        return $streak;
    }

    protected function getWeeklyActivity(int $studentId): array
    {
        $activity = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $activity[] = [
                'date' => $date->format('D'),
                'lessons_completed' => LessonProgress::where('student_id', $studentId)
                    ->whereDate('updated_at', $date)
                    ->where('status', 'completed')
                    ->count(),
                'quizzes_taken' => QuizAttempt::where('student_id', $studentId)
                    ->whereDate('created_at', $date)
                    ->count(),
            ];
        }

        return $activity;
    }

    protected function getCompletionTimeline(int $studentId): array
    {
        return CourseCompletion::where('student_id', $studentId)
            ->with('class.course')
            ->orderBy('completed_at')
            ->get()
            ->map(function ($completion) {
                return [
                    'course' => $completion->class->course->title,
                    'completed_at' => $completion->completed_at->format('M j, Y'),
                    'completion_percent' => $completion->completion_percent,
                ];
            })
            ->toArray();
    }
}
