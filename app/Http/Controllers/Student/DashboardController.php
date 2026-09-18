<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\BadgeAward;
use App\Models\CourseCompletion;
use App\Models\CourseProgress;
use App\Models\Enrollment;
use App\Models\Feedback;
use App\Models\Grade;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\VirtualClass;
use App\Services\StudentPerformanceAssessmentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private StudentPerformanceAssessmentService $performanceAssessment) {}

    public function __invoke(): View
    {
        $studentId = auth()->id();

        // Get student's enrollments
        $enrollments = Enrollment::where('student_id', $studentId)
            ->with(['class.course', 'class.instructor', 'class.academicPeriod'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate statistics
        $activeEnrollments = $enrollments->where('status', 'active');
        $completedEnrollments = $enrollments->where('status', 'completed');

        // Get class IDs and course IDs for queries
        $classIds = $activeEnrollments->pluck('class_id');
        $courseIds = $activeEnrollments->pluck('class.course_id')->filter();

        // Upcoming assignments
        $upcomingAssignments = Assignment::whereIn('class_id', $classIds)
            ->where('status', 'published')
            ->where('due_date', '>', now())
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Overdue assignments
        $overdueAssignments = Assignment::whereIn('class_id', $classIds)
            ->where('status', 'published')
            ->where('due_date', '<', now())
            ->whereDoesntHave('submissions', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->orderBy('due_date', 'desc')
            ->limit(5)
            ->get();

        // Upcoming quizzes
        $upcomingQuizzes = Quiz::whereIn('class_id', $classIds)
            ->where('status', 'published')
            ->where('availability_from', '>', now())
            ->orderBy('availability_from')
            ->limit(5)
            ->get();

        // Recent quiz attempts
        $recentQuizAttempts = QuizAttempt::where('student_id', $studentId)
            ->with(['quiz.class.course'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Upcoming virtual classes
        $upcomingVirtualClasses = VirtualClass::whereIn('class_id', $classIds)
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        // Recent grades
        $recentGrades = Grade::where('student_id', $studentId)
            ->with('item')
            ->whereHas('item', fn ($q) => $q->where('is_released', true))
            ->orderBy('graded_at', 'desc')
            ->limit(5)
            ->get();

        // Recent feedback
        $recentFeedback = Feedback::where('student_id', $studentId)
            ->with('gradable')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Course progress calculations
        $courseProgressData = [];
        $overallProgress = 0;

        foreach ($activeEnrollments as $enrollment) {
            $course = $enrollment->class->course;
            if ($course) {
                $progress = CourseProgress::where('student_id', $studentId)
                    ->where('class_id', $enrollment->class_id)
                    ->first();

                $courseProgressData[] = [
                    'course' => $course,
                    'progress' => $progress ? $progress->progress_percent : 0,
                    'last_accessed' => $progress ? $progress->updated_at : null,
                ];

                $overallProgress += ($progress ? $progress->progress_percent : 0);
            }
        }

        $overallProgress = $activeEnrollments->isNotEmpty() ? $overallProgress / $activeEnrollments->count() : 0;

        // Continue learning (most recent course with progress)
        $continueLearning = collect($courseProgressData)
            ->sortByDesc('last_accessed')
            ->first();

        // Certificates
        $certificates = CourseCompletion::where('student_id', $studentId)
            ->with('certificate')
            ->whereHas('certificate')
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        // Badges
        $badges = BadgeAward::where('student_id', $studentId)
            ->with('badge')
            ->orderBy('issued_at', 'desc')
            ->limit(5)
            ->get();

        // Announcements for student's courses
        $announcements = Announcement::where(function ($q) use ($courseIds, $classIds) {
            $q->whereIn('course_id', $courseIds)
                ->orWhereIn('class_id', $classIds);
        })
            ->where('publish_at', '<=', now())
            ->orderBy('publish_at', 'desc')
            ->limit(5)
            ->get();

        // Learning streak (consecutive days with activity)
        $learningStreak = $this->calculateLearningStreak($studentId);

        // Academic progress summary
        $gpa = $this->calculateGPA($completedEnrollments);

        $performanceAssessment = $this->performanceAssessment->assess(
            $studentId,
            $classIds,
            $overallProgress,
            $learningStreak,
        );

        $stats = [
            // Course Overview
            'my_courses' => $activeEnrollments->count(),
            'total_enrollments' => $enrollments->count(),
            'active_enrollments' => $activeEnrollments->count(),
            'completed_enrollments' => $completedEnrollments->count(),

            // Academic Progress
            'average_grade' => $completedEnrollments->whereNotNull('final_grade')->avg('final_grade') ?? 0,
            'highest_grade' => $completedEnrollments->whereNotNull('final_grade')->max('final_grade') ?? 0,
            'lowest_grade' => $completedEnrollments->whereNotNull('final_grade')->min('final_grade') ?? 0,
            'gpa' => $gpa,

            // Course Progress
            'course_progress' => $courseProgressData,
            'overall_progress' => round($overallProgress, 2),
            'continue_learning' => $continueLearning,

            // Assignments
            'upcoming_assignments' => $upcomingAssignments,
            'overdue_assignments' => $overdueAssignments,
            'total_assignments' => Assignment::whereIn('class_id', $classIds)
                ->count(),

            // Quizzes
            'upcoming_quizzes' => $upcomingQuizzes,
            'recent_quiz_attempts' => $recentQuizAttempts,
            'total_quizzes' => Quiz::whereIn('class_id', $classIds)
                ->count(),

            // Virtual Classes
            'upcoming_virtual_classes' => $upcomingVirtualClasses,
            'total_virtual_classes' => VirtualClass::whereIn('class_id', $classIds)->count(),

            // Grades and Feedback
            'recent_grades' => $recentGrades,
            'recent_feedback' => $recentFeedback,
            'total_grades' => Grade::where('student_id', $studentId)->count(),

            // Achievements
            'certificates' => $certificates,
            'badges' => $badges,
            'total_certificates' => $certificates->count(),
            'total_badges' => $badges->count(),

            // Communication
            'announcements' => $announcements,
            'total_announcements' => $announcements->count(),

            // Engagement
            'learning_streak' => $learningStreak,
            'performance_assessment' => $performanceAssessment,

            // Recent Activity
            'recent_enrollments' => $enrollments->take(5),
            'my_enrollments' => $activeEnrollments,

            // Academic Period Info
            'current_period' => $activeEnrollments->first()?->class?->academicPeriod,
        ];

        return view('student.dashboard', compact('stats'));
    }

    protected function calculateLearningStreak(int $studentId): int
    {
        $today = Carbon::today();
        $streak = 0;

        // Check for lesson progress activity
        for ($i = 0; $i < 30; $i++) { // Check last 30 days
            $date = $today->copy()->subDays($i);
            $hasActivity = LessonProgress::where('student_id', $studentId)
                ->whereDate('updated_at', $date)
                ->exists();

            if ($hasActivity) {
                $streak++;
            } elseif ($i > 0) { // Allow today to have no activity without breaking streak
                break;
            }
        }

        return $streak;
    }

    protected function calculateGPA($completedEnrollments): float
    {
        if ($completedEnrollments->isEmpty()) {
            return 0.0;
        }

        $totalPoints = 0;
        $totalCredits = 0;

        foreach ($completedEnrollments as $enrollment) {
            if ($enrollment->final_grade) {
                $gradePoints = $this->percentageToGradePoints($enrollment->final_grade);
                $credits = $enrollment->class->course->credits ?? 3; // Default to 3 credits if not specified

                $totalPoints += $gradePoints * $credits;
                $totalCredits += $credits;
            }
        }

        return $totalCredits > 0 ? $totalPoints / $totalCredits : 0.0;
    }

    protected function percentageToGradePoints(float $percentage): float
    {
        if ($percentage >= 90) {
            return 4.0;
        }
        if ($percentage >= 80) {
            return 3.0;
        }
        if ($percentage >= 70) {
            return 2.0;
        }
        if ($percentage >= 60) {
            return 1.0;
        }

        return 0.0;
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(): RedirectResponse
    {
        Cache::forget('student_dashboard_'.auth()->id());

        return redirect()->route('student.dashboard')
            ->with('success', 'Dashboard cache cleared successfully.');
    }

    /**
     * Real-time stats for auto-refresh
     */
    public function getRealTimeStats()
    {
        try {
            $studentId = auth()->id();
            $stats = $this->calculateStats();

            return response()->json([
                'success' => true,
                'data' => [
                    'upcoming_tasks' => $stats['upcoming_assignments']->count() + $stats['upcoming_quizzes']->count(),
                    'overdue_count' => $stats['overdue_assignments']->count(),
                    'learning_streak' => $stats['learning_streak'],
                    'recent_grades_count' => $stats['recent_grades']->count(),
                    'my_courses' => $stats['my_courses'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch real-time stats: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search within the student's own courses/assignments/quizzes
     */
    public function search(Request $request)
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string|min:2',
                'type' => 'required|in:all,courses,assignments,quizzes',
            ]);

            $studentId = auth()->id();
            $query = $validated['query'];
            $type = $validated['type'];
            $results = [];
            $types = $type === 'all' ? ['courses', 'assignments', 'quizzes'] : [$type];

            foreach ($types as $t) {
                $results = array_merge($results, $this->searchType($t, $query, $studentId));
            }

            return response()->json([
                'success' => true,
                'data' => array_slice($results, 0, 30),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Search failed: '.$e->getMessage(),
            ], 500);
        }
    }

    private function searchType(string $type, string $query, int $studentId): array
    {
        $classIds = Enrollment::where('student_id', $studentId)
            ->where('status', 'active')
            ->pluck('class_id');

        switch ($type) {
            case 'courses':
                return Enrollment::where('student_id', $studentId)
                    ->whereHas('class.course', function ($q) use ($query) {
                        $q->where('title', 'like', "%{$query}%")
                            ->orWhere('code', 'like', "%{$query}%");
                    })
                    ->with('class.course')
                    ->limit(10)
                    ->get()
                    ->map(fn ($e) => [
                        'id' => $e->class->course->id,
                        'title' => $e->class->course->title,
                        'code' => $e->class->course->code,
                        'class_code' => $e->class->code,
                    ])
                    ->toArray();

            case 'assignments':
                return Assignment::whereIn('class_id', $classIds)
                    ->where('title', 'like', "%{$query}%")
                    ->with('class.course')
                    ->limit(10)
                    ->get()
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'title' => $a->title,
                        'class_code' => $a->class->code ?? '',
                        'course_title' => $a->class->course->title ?? '',
                        'due_date' => $a->due_date?->format('Y-m-d'),
                        'status' => $a->status,
                    ])
                    ->toArray();

            case 'quizzes':
                return Quiz::whereIn('class_id', $classIds)
                    ->where('title', 'like', "%{$query}%")
                    ->with('class.course')
                    ->limit(10)
                    ->get()
                    ->map(fn ($q) => [
                        'id' => $q->id,
                        'title' => $q->title,
                        'class_code' => $q->class->code ?? '',
                        'course_title' => $q->class->course->title ?? '',
                        'availability_from' => $q->availability_from?->format('Y-m-d'),
                        'status' => $q->status,
                    ])
                    ->toArray();

            default:
                return [];
        }
    }

    /**
     * Analytics for charts (grade trend, quiz performance, course progress)
     */
    public function getAnalytics(Request $request)
    {
        try {
            $type = $request->input('type', 'grades');
            if (! in_array($type, ['grades', 'quizzes', 'progress'], true)) {
                $type = 'grades';
            }

            $studentId = auth()->id();

            switch ($type) {
                case 'grades':
                    $grades = Grade::where('student_id', $studentId)
                        ->with('item')
                        ->whereHas('item', fn ($q) => $q->where('is_released', true))
                        ->orderBy('graded_at')
                        ->get()
                        ->map(fn ($g) => [
                            'name' => $g->item?->name ?? 'Grade',
                            'score' => round($g->score_percent ?? 0, 1),
                            'date' => $g->graded_at?->format('M j'),
                        ]);

                    $analytics = [
                        'labels' => $grades->pluck('name')->toArray(),
                        'scores' => $grades->pluck('score')->toArray(),
                        'dates' => $grades->pluck('date')->toArray(),
                        'average' => round($grades->avg('score') ?? 0, 1),
                        'total_grades' => $grades->count(),
                    ];
                    break;

                case 'quizzes':
                    $attempts = QuizAttempt::where('student_id', $studentId)
                        ->with('quiz')
                        ->orderBy('created_at')
                        ->get()
                        ->map(fn ($a) => [
                            'quiz' => $a->quiz?->title ?? 'Quiz',
                            'score' => round($a->score_percent ?? $a->score ?? 0, 1),
                        ]);

                    $analytics = [
                        'labels' => $attempts->pluck('quiz')->map(fn ($t) => Str::limit($t, 16))->toArray(),
                        'scores' => $attempts->pluck('score')->toArray(),
                        'total_attempts' => $attempts->count(),
                        'best_score' => round(max($attempts->pluck('score')->toArray() ?: [0]), 1),
                    ];
                    break;

                case 'progress':
                    $enrollments = Enrollment::where('student_id', $studentId)
                        ->where('status', 'active')
                        ->with('class.course')
                        ->get();

                    $progressData = [];
                    foreach ($enrollments as $enrollment) {
                        $progress = CourseProgress::where('student_id', $studentId)
                            ->where('class_id', $enrollment->class_id)
                            ->first();
                        $progressData[] = [
                            'course' => $enrollment->class->course->title ?? 'Course',
                            'progress' => round($progress?->progress_percent ?? 0, 1),
                        ];
                    }

                    $analytics = [
                        'labels' => collect($progressData)->pluck('course')->map(fn ($t) => Str::limit($t, 16))->toArray(),
                        'progress' => collect($progressData)->pluck('progress')->toArray(),
                        'overall' => round(collect($progressData)->avg('progress') ?? 0, 1),
                        'total_courses' => count($progressData),
                    ];
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch analytics: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lightweight stats for real-time endpoint (avoids full dashboard render)
     */
    private function calculateStats(): array
    {
        $studentId = auth()->id();
        $enrollments = Enrollment::where('student_id', $studentId)
            ->with('class.course')
            ->get();

        $activeEnrollments = $enrollments->where('status', 'active');
        $classIds = $activeEnrollments->pluck('class_id');

        $upcomingAssignments = Assignment::whereIn('class_id', $classIds)
            ->where('status', 'published')
            ->where('due_date', '>', now())
            ->orderBy('due_date')->limit(5)->get();

        $upcomingQuizzes = Quiz::whereIn('class_id', $classIds)
            ->where('status', 'published')
            ->where('availability_from', '>', now())
            ->orderBy('availability_from')->limit(5)->get();

        $overdueAssignments = Assignment::whereIn('class_id', $classIds)
            ->where('status', 'published')
            ->where('due_date', '<', now())
            ->whereDoesntHave('submissions', fn ($q) => $q->where('student_id', $studentId))
            ->orderBy('due_date', 'desc')->limit(5)->get();

        $recentGrades = Grade::where('student_id', $studentId)
            ->with('item')
            ->whereHas('item', fn ($q) => $q->where('is_released', true))
            ->orderBy('graded_at', 'desc')->limit(5)->get();

        $assessment = $this->performanceAssessment->assess(
            $studentId,
            $classIds,
            0,
            $this->calculateLearningStreak($studentId),
        );

        return [
            'my_courses' => $activeEnrollments->count(),
            'upcoming_assignments' => $upcomingAssignments,
            'upcoming_quizzes' => $upcomingQuizzes,
            'overdue_assignments' => $overdueAssignments,
            'recent_grades' => $recentGrades,
            'learning_streak' => $this->calculateLearningStreak($studentId),
            'performance_assessment' => $assessment,
        ];
    }
}
