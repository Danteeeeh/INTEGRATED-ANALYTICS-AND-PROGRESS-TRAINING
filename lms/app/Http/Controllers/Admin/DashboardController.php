<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseCompletion;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\LessonProgress;
use App\Models\Notification;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\User;
use App\Models\VirtualClass;
use App\Models\VirtualClassAttendee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $currentPeriod = AcademicPeriod::where('is_current', true)->first();

        $stats = [
            // User Statistics
            'total_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))->count(),
            'total_instructors' => User::whereHas('role', fn ($q) => $q->where('slug', Role::INSTRUCTOR))->count(),
            'total_admins' => User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN))->count(),
            'active_users' => User::where('users.status', 'active')->count(),
            'inactive_users' => User::where('users.status', 'inactive')->count(),
            'pending_users' => User::where('users.status', 'pending')->count(),

            // Academic Statistics
            'total_courses' => Course::count(),
            'published_courses' => Course::where('courses.status', 'published')->count(),
            'draft_courses' => Course::where('courses.status', 'draft')->count(),
            'archived_courses' => Course::where('courses.status', 'archived')->count(),
            'total_classes' => ClassModel::count(),
            'active_classes' => ClassModel::where('classes.status', 'active')->count(),

            // Enrollment Statistics
            'active_enrollments' => Enrollment::where('enrollments.status', 'active')->count(),
            'pending_enrollments' => Enrollment::where('enrollments.status', 'pending')->count(),
            'completed_enrollments' => Enrollment::where('enrollments.status', 'completed')->count(),
            'dropped_enrollments' => Enrollment::where('enrollments.status', 'dropped')->count(),

            // Assignment Activity
            'total_assignments' => Assignment::count(),
            'published_assignments' => Assignment::where('assignments.status', 'published')->count(),
            'pending_submissions' => AssignmentSubmission::where('assignment_submissions.status', 'submitted')->count(),
            'graded_submissions' => AssignmentSubmission::where('assignment_submissions.status', 'graded')->count(),
            'assignment_completion_rate' => $this->calculateAssignmentCompletionRate(),

            // Quiz Activity
            'total_quizzes' => Quiz::count(),
            'published_quizzes' => Quiz::where('quizzes.status', 'published')->count(),
            'quiz_attempts' => QuizAttempt::count(),
            'quiz_completion_rate' => $this->calculateQuizCompletionRate(),
            'average_quiz_score' => QuizAttempt::whereNotNull('score')->avg('score') ?? 0,

            // Course Completion
            'total_completions' => CourseCompletion::count(),
            'completion_rate' => $this->calculateCourseCompletionRate(),
            'average_completion_percentage' => CourseCompletion::avg('completion_percent') ?? 0,

            // Grades
            'total_grades' => Grade::count(),
            'average_grade' => Grade::whereNotNull('score_percent')->avg('score_percent') ?? 0,

            // Attendance
            'total_attendance_records' => AttendanceRecord::count(),
            'present_count' => AttendanceRecord::where('attendance_records.status', 'present')->count(),
            'absent_count' => AttendanceRecord::where('attendance_records.status', 'absent')->count(),
            'late_count' => AttendanceRecord::where('attendance_records.status', 'late')->count(),
            'attendance_rate' => $this->calculateAttendanceRate(),

            // Academic Period
            'current_period' => $currentPeriod,
            'total_periods' => AcademicPeriod::count(),

            // Notifications
            'total_notifications' => Notification::count(),
            'unread_notifications' => Notification::whereNull('read_at')->count(),

            // Recent Activity
            'recent_users' => User::with('role')->orderBy('created_at', 'desc')->limit(5)->get(),
            'recent_courses' => Course::orderBy('created_at', 'desc')->limit(5)->get(),
            'recent_enrollments' => Enrollment::with(['student', 'class.course'])->orderBy('created_at', 'desc')->limit(5)->get(),
            'recent_audit_logs' => AuditLog::orderBy('created_at', 'desc')->limit(5)->get(),
            'recent_assignments' => Assignment::orderBy('created_at', 'desc')->limit(5)->get(),
            'recent_quizzes' => Quiz::orderBy('created_at', 'desc')->limit(5)->get(),

            // Course Statistics
            'courses_with_completion' => Course::withCount('enrollments')->get(),
            'courses_by_status' => [
                'published' => Course::where('courses.status', 'published')->count(),
                'draft' => Course::where('courses.status', 'draft')->count(),
                'archived' => Course::where('courses.status', 'archived')->count(),
            ],

            // System Health
            'database_status' => $this->checkDatabaseStatus(),
            'storage_usage' => $this->getStorageUsage(),

            // Advanced Analytics
            'learning_analytics' => $this->getLearningAnalytics(),
            'engagement_metrics' => $this->getEngagementMetrics(),
            'performance_trends' => $this->getPerformanceTrends(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Clear admin dashboard cache
     */
    public function clearCache(): RedirectResponse
    {
        Cache::forget('admin_dashboard');

        return redirect()->route('admin.dashboard')
            ->with('success', 'Dashboard cache cleared successfully.');
    }

    /**
     * Real-time stats for the admin dashboard tiles
     */
    public function getRealTimeStats()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))->count(),
                    'total_instructors' => User::whereHas('role', fn ($q) => $q->where('slug', Role::INSTRUCTOR))->count(),
                    'total_classes' => ClassModel::count(),
                    'active_enrollments' => Enrollment::where('enrollments.status', 'active')->count(),
                    'pending_enrollments' => Enrollment::where('enrollments.status', 'pending')->count(),
                    'pending_submissions' => AssignmentSubmission::where('assignment_submissions.status', 'submitted')->count(),
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
     * Analytics data (JSON) for dashboard charts
     */
    public function getAnalyticsJson()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'enrollment' => [
                        'active' => Enrollment::where('enrollments.status', 'active')->count(),
                        'pending' => Enrollment::where('enrollments.status', 'pending')->count(),
                        'completed' => Enrollment::where('enrollments.status', 'completed')->count(),
                        'dropped' => Enrollment::where('enrollments.status', 'dropped')->count(),
                    ],
                    'courses_by_status' => [
                        'published' => Course::where('courses.status', 'published')->count(),
                        'draft' => Course::where('courses.status', 'draft')->count(),
                        'archived' => Course::where('courses.status', 'archived')->count(),
                    ],
                    'users_by_role' => [
                        'admins' => User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN))->count(),
                        'instructors' => User::whereHas('role', fn ($q) => $q->where('slug', Role::INSTRUCTOR))->count(),
                        'students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))->count(),
                        'registrars' => User::whereHas('role', fn ($q) => $q->where('slug', Role::REGISTRAR))->count(),
                    ],
                    'attendance' => [
                        'present' => AttendanceRecord::where('attendance_records.status', 'present')->count(),
                        'absent' => AttendanceRecord::where('attendance_records.status', 'absent')->count(),
                        'late' => AttendanceRecord::where('attendance_records.status', 'late')->count(),
                    ],
                    'activity' => AuditLog::selectRaw('DATE(created_at) as date, count(*) as total')
                        ->where('created_at', '>=', now()->subDays(14))
                        ->groupBy('date')->orderBy('date')
                        ->get()
                        ->map(fn ($l) => ['date' => $l->date, 'total' => $l->total])
                        ->toArray(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch analytics: '.$e->getMessage(),
            ], 500);
        }
    }

    public function analytics(): View
    {
        $currentPeriod = AcademicPeriod::where('is_current', true)->first();

        $stats = [
            // User Statistics
            'total_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))->count(),
            'total_instructors' => User::whereHas('role', fn ($q) => $q->where('slug', Role::INSTRUCTOR))->count(),
            'total_admins' => User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN))->count(),
            'active_users' => User::where('users.status', 'active')->count(),
            'inactive_users' => User::where('users.status', 'inactive')->count(),
            'pending_users' => User::where('users.status', 'pending')->count(),

            // Academic Statistics
            'total_courses' => Course::count(),
            'published_courses' => Course::where('courses.status', 'published')->count(),
            'draft_courses' => Course::where('courses.status', 'draft')->count(),
            'archived_courses' => Course::where('courses.status', 'archived')->count(),
            'total_classes' => ClassModel::count(),
            'active_classes' => ClassModel::where('classes.status', 'active')->count(),

            // Enrollment Statistics
            'active_enrollments' => Enrollment::where('enrollments.status', 'active')->count(),
            'pending_enrollments' => Enrollment::where('enrollments.status', 'pending')->count(),
            'completed_enrollments' => Enrollment::where('enrollments.status', 'completed')->count(),
            'dropped_enrollments' => Enrollment::where('enrollments.status', 'dropped')->count(),

            // Assignment Activity
            'total_assignments' => Assignment::count(),
            'published_assignments' => Assignment::where('assignments.status', 'published')->count(),
            'pending_submissions' => AssignmentSubmission::where('assignment_submissions.status', 'submitted')->count(),
            'graded_submissions' => AssignmentSubmission::where('assignment_submissions.status', 'graded')->count(),
            'assignment_completion_rate' => $this->calculateAssignmentCompletionRate(),

            // Quiz Activity
            'total_quizzes' => Quiz::count(),
            'published_quizzes' => Quiz::where('quizzes.status', 'published')->count(),
            'quiz_attempts' => QuizAttempt::count(),
            'quiz_completion_rate' => $this->calculateQuizCompletionRate(),
            'average_quiz_score' => QuizAttempt::whereNotNull('score')->avg('score') ?? 0,

            // Course Completion
            'total_completions' => CourseCompletion::count(),
            'completion_rate' => $this->calculateCourseCompletionRate(),
            'average_completion_percentage' => CourseCompletion::avg('completion_percent') ?? 0,

            // Grades
            'total_grades' => Grade::count(),
            'average_grade' => Grade::whereNotNull('score_percent')->avg('score_percent') ?? 0,

            // Attendance
            'total_attendance_records' => AttendanceRecord::count(),
            'present_count' => AttendanceRecord::where('attendance_records.status', 'present')->count(),
            'absent_count' => AttendanceRecord::where('attendance_records.status', 'absent')->count(),
            'late_count' => AttendanceRecord::where('attendance_records.status', 'late')->count(),
            'attendance_rate' => $this->calculateAttendanceRate(),

            // Academic Period
            'current_period' => $currentPeriod,
            'total_periods' => AcademicPeriod::count(),

            // Notifications
            'total_notifications' => Notification::count(),
            'unread_notifications' => Notification::whereNull('read_at')->count(),

            // System Health
            'database_status' => $this->checkDatabaseStatus(),
            'storage_usage' => $this->getStorageUsage(),

            // Advanced Analytics
            'learning_analytics' => $this->getLearningAnalytics(),
            'engagement_metrics' => $this->getEngagementMetrics(),
            'performance_trends' => $this->getPerformanceTrends(),
        ];

        return view('admin.analytics', compact('stats'));
    }

    protected function getLearningAnalytics(): array
    {
        return [
            'average_time_to_complete' => $this->calculateAverageCompletionTime(),
            'most_popular_courses' => $this->getMostPopularCourses(),
            'completion_by_category' => $this->getCompletionByCategory(),
            'student_retention_rate' => $this->calculateRetentionRate(),
        ];
    }

    protected function getEngagementMetrics(): array
    {
        return [
            'daily_active_users' => $this->getDailyActiveUsers(),
            'content_consumption' => $this->getContentConsumption(),
            'forum_participation' => $this->getForumParticipation(),
            'virtual_class_attendance' => $this->getVirtualClassAttendance(),
        ];
    }

    protected function getPerformanceTrends(): array
    {
        return [
            'grade_distribution' => $this->getGradeDistribution(),
            'improvement_rate' => $this->calculateImprovementRate(),
            'at_risk_students' => $this->getAtRiskStudents(),
            'top_performers' => $this->getTopPerformers(),
        ];
    }

    protected function calculateAverageCompletionTime(): float
    {
        $completions = CourseCompletion::with('class.enrollments')
            ->whereNotNull('completed_at')
            ->get();

        if ($completions->isEmpty()) {
            return 0;
        }

        $totalDays = $completions->sum(function ($completion) {
            $enrollment = $completion->class->enrollments
                ->where('student_id', $completion->student_id)
                ->where('enrollments.status', 'active')
                ->first();

            if (! $enrollment || ! $enrollment->enrolled_at) {
                return 0;
            }

            return $completion->completed_at->diffInDays($enrollment->enrolled_at);
        });

        return $totalDays / $completions->count();
    }

    protected function getMostPopularCourses(): array
    {
        return Course::withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($course) {
                return [
                    'title' => $course->title,
                    'enrollments' => $course->enrollments_count,
                    'completion_rate' => $course->enrollments()->where('enrollments.status', 'completed')->count() / max($course->enrollments_count, 1) * 100,
                ];
            })
            ->toArray();
    }

    protected function getCompletionByCategory(): array
    {
        return CourseCategory::with('courses.enrollments')
            ->get()
            ->map(function ($category) {
                $totalEnrollments = $category->courses->sum(function ($course) {
                    return $course->enrollments()->count();
                });
                $completedEnrollments = $category->courses->sum(function ($course) {
                    return $course->enrollments()->where('enrollments.status', 'completed')->count();
                });

                return [
                    'name' => $category->name,
                    'total' => $totalEnrollments,
                    'completed' => $completedEnrollments,
                    'rate' => $totalEnrollments > 0 ? ($completedEnrollments / $totalEnrollments) * 100 : 0,
                ];
            })
            ->toArray();
    }

    protected function calculateRetentionRate(): float
    {
        $totalEnrollments = Enrollment::count();
        $completedEnrollments = Enrollment::where('enrollments.status', 'completed')->count();
        $droppedEnrollments = Enrollment::where('enrollments.status', 'dropped')->count();

        if ($totalEnrollments === 0) {
            return 0;
        }

        return (($totalEnrollments - $droppedEnrollments) / $totalEnrollments) * 100;
    }

    protected function getDailyActiveUsers(): array
    {
        $last30Days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = User::whereDate('last_login_at', $date)->count();
            $last30Days->put($date, $count);
        }

        return $last30Days->toArray();
    }

    protected function getContentConsumption(): array
    {
        return [
            'total_lessons_viewed' => LessonProgress::where('lesson_progress.status', LessonProgress::STATUS_COMPLETED)->count(),
            'total_videos_watched' => LessonProgress::whereHas('lesson', function ($q) {
                $q->where('lesson_type', 'video');
            })->where('lesson_progress.status', LessonProgress::STATUS_COMPLETED)->count(),
            'average_lesson_completion' => LessonProgress::avg('progress_percent') ?? 0,
        ];
    }

    protected function getForumParticipation(): array
    {
        return [
            'total_posts' => DiscussionPost::count(),
            'active_discussions' => Discussion::where('discussions.created_at', '>', now()->subDays(7))->count(),
            'top_contributors' => DiscussionPost::select('author_id')
                ->selectRaw('count(*) as post_count')
                ->groupBy('author_id')
                ->orderBy('post_count', 'desc')
                ->limit(5)
                ->with('author')
                ->get()
                ->toArray(),
        ];
    }

    protected function getVirtualClassAttendance(): array
    {
        return [
            'total_classes' => VirtualClass::count(),
            'average_attendance' => VirtualClassAttendee::where('attendance_status', 'present')->count() / max(VirtualClass::count(), 1),
            'attendance_rate' => VirtualClass::withCount('attendees')
                ->get()
                ->avg(function ($class) {
                    return $class->attendees_count / max($class->enrollments()->count(), 1) * 100;
                }) ?? 0,
        ];
    }

    protected function getGradeDistribution(): array
    {
        $grades = Grade::whereNotNull('score_percent')->get();

        return [
            'a' => $grades->where('score_percent', '>=', 90)->count(),
            'b' => $grades->where('score_percent', '>=', 80)->where('score_percent', '<', 90)->count(),
            'c' => $grades->where('score_percent', '>=', 70)->where('score_percent', '<', 80)->count(),
            'd' => $grades->where('score_percent', '>=', 60)->where('score_percent', '<', 70)->count(),
            'f' => $grades->where('score_percent', '<', 60)->count(),
        ];
    }

    protected function calculateImprovementRate(): float
    {
        // Calculate improvement in grades over time
        $currentMonthGrades = Grade::whereMonth('created_at', now()->month)
            ->whereNotNull('score_percent')
            ->avg('score_percent') ?? 0;

        $previousMonthGrades = Grade::whereMonth('created_at', now()->subMonth()->month)
            ->whereNotNull('score_percent')
            ->avg('score_percent') ?? 0;

        if ($previousMonthGrades === 0) {
            return 0;
        }

        return (($currentMonthGrades - $previousMonthGrades) / $previousMonthGrades) * 100;
    }

    protected function getAtRiskStudents(): array
    {
        return Enrollment::where('enrollments.status', 'active')
            ->whereHas('student', function ($q) {
                $q->whereHas('grades', function ($q) {
                    $q->where('score_percent', '<', 60);
                });
            })
            ->with('student')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getTopPerformers(): array
    {
        return User::whereHas('role', function ($q) {
            $q->where('slug', Role::STUDENT);
        })
            ->whereHas('grades', function ($q) {
                $q->where('score_percent', '>=', 90);
            })
            ->with('grades')
            ->limit(10)
            ->get()
            ->map(function ($student) {
                return [
                    'name' => $student->name,
                    'average_grade' => $student->grades->avg('score_percent') ?? 0,
                    'total_grades' => $student->grades->count(),
                ];
            })
            ->toArray();
    }

    protected function calculateAssignmentCompletionRate(): float
    {
        $totalAssignments = Assignment::where('assignments.status', 'published')->count();
        if ($totalAssignments === 0) {
            return 0;
        }

        $submittedAssignments = AssignmentSubmission::whereHas('assignment', function ($query) {
            $query->where('assignments.status', 'published');
        })->count();

        return ($submittedAssignments / $totalAssignments) * 100;
    }

    protected function calculateQuizCompletionRate(): float
    {
        $totalQuizzes = Quiz::where('quizzes.status', 'published')->count();
        if ($totalQuizzes === 0) {
            return 0;
        }

        $attemptedQuizzes = QuizAttempt::whereHas('quiz', function ($query) {
            $query->where('quizzes.status', 'published');
        })->distinct('quiz_id')->count();

        return ($attemptedQuizzes / $totalQuizzes) * 100;
    }

    protected function calculateCourseCompletionRate(): float
    {
        $totalEnrollments = Enrollment::where('enrollments.status', 'active')->count();
        if ($totalEnrollments === 0) {
            return 0;
        }

        $totalCompletions = CourseCompletion::count();

        return ($totalCompletions / $totalEnrollments) * 100;
    }

    protected function calculateAttendanceRate(): float
    {
        $totalRecords = AttendanceRecord::count();
        if ($totalRecords === 0) {
            return 0;
        }

        $presentRecords = AttendanceRecord::where('attendance_records.status', 'present')->count();

        return ($presentRecords / $totalRecords) * 100;
    }

    protected function checkDatabaseStatus(): string
    {
        try {
            \DB::connection()->getPdo();

            return 'connected';
        } catch (\Exception $e) {
            return 'disconnected';
        }
    }

    protected function getStorageUsage(): array
    {
        try {
            $totalSpace = disk_total_space(storage_path());
            $freeSpace = disk_free_space(storage_path());
            $usedSpace = $totalSpace - $freeSpace;
            $usagePercentage = ($usedSpace / $totalSpace) * 100;

            return [
                'total' => $this->formatBytes($totalSpace),
                'used' => $this->formatBytes($usedSpace),
                'free' => $this->formatBytes($freeSpace),
                'percentage' => round($usagePercentage, 2),
            ];
        } catch (\Exception $e) {
            return [
                'total' => 'N/A',
                'used' => 'N/A',
                'free' => 'N/A',
                'percentage' => 0,
            ];
        }
    }

    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    public function sms(): View
    {
        $currentPeriod = AcademicPeriod::where('is_current', true)->first();

        $stats = [
            // User Statistics
            'total_students' => User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))->count(),
            'total_instructors' => User::whereHas('role', fn ($q) => $q->where('slug', Role::INSTRUCTOR))->count(),
            'total_admins' => User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN))->count(),
            'active_users' => User::where('users.status', 'active')->count(),

            // Academic Statistics
            'total_courses' => Course::count(),
            'published_courses' => Course::where('courses.status', 'published')->count(),
            'total_classes' => ClassModel::count(),
            'active_classes' => ClassModel::where('classes.status', 'active')->count(),

            // Enrollment Statistics
            'active_enrollments' => Enrollment::where('enrollments.status', 'active')->count(),
            'pending_enrollments' => Enrollment::where('enrollments.status', 'pending')->count(),

            // Academic Period
            'current_period' => $currentPeriod,

            // Recent Activity
            'recent_users' => User::with('role')->orderBy('created_at', 'desc')->limit(5)->get(),
            'recent_courses' => Course::orderBy('created_at', 'desc')->limit(5)->get(),
        ];

        return view('admin.dashboard-sms', compact('stats'));
    }
}
