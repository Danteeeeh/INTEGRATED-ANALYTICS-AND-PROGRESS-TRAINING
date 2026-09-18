<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $instructorId = auth()->id();
        $cacheKey = "instructor_dashboard_{$instructorId}";
        $cacheDuration = now()->addMinutes(15);

        // Use caching for better performance
        $stats = Cache::remember($cacheKey, $cacheDuration, function () use ($instructorId) {
            return $this->calculateDashboardStats($instructorId);
        });

        return view('instructor.dashboard', compact('stats'));
    }

    private function calculateDashboardStats($instructorId): array
    {
        $instructor = User::find($instructorId);

        // Get instructor's classes with optimized eager loading
        $myClasses = ClassModel::where('instructor_id', $instructorId)
            ->with(['course', 'enrollments.student'])
            ->get();

        // Get instructor's courses (created by them)
        $myCourses = Course::where('created_by', $instructorId)->get();

        // Calculate statistics with optimized queries
        $totalStudents = 0;
        $activeEnrollments = 0;
        $completedEnrollments = 0;
        $gradedEnrollments = 0;

        foreach ($myClasses as $class) {
            $totalStudents += $class->enrollments->count();
            $activeEnrollments += $class->enrollments->where('status', 'active')->count();
            $completedEnrollments += $class->enrollments->where('status', 'completed')->count();
            $gradedEnrollments += $class->enrollments->whereNotNull('final_grade')->count();
        }

        // Get instructor's class IDs
        $classIds = $myClasses->pluck('id');
        $courseIds = $myCourses->pluck('id');

        // Assignment metrics with optimized queries
        $pendingSubmissions = AssignmentSubmission::whereHas('assignment', function ($query) use ($classIds, $courseIds) {
            $query->whereIn('class_id', $classIds)
                ->orWhereIn('course_id', $courseIds);
        })->where('status', 'submitted')->count();

        $upcomingAssignments = Assignment::whereHas('class', function ($query) use ($classIds) {
            $query->whereIn('id', $classIds);
        })->where('status', 'published')
            ->where('due_date', '>', now())
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Quiz metrics with optimized queries
        $upcomingQuizzes = Quiz::whereHas('class', function ($query) use ($classIds) {
            $query->whereIn('id', $classIds);
        })->where('status', 'published')
            ->where('availability_from', '>', now())
            ->orderBy('availability_from')
            ->limit(5)
            ->get();

        $recentQuizAttempts = QuizAttempt::whereHas('quiz', function ($query) use ($classIds, $courseIds) {
            $query->whereIn('class_id', $classIds)
                ->orWhereIn('course_id', $courseIds);
        })->with(['student', 'quiz'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Virtual classes with optimized queries
        $upcomingVirtualClasses = VirtualClass::whereIn('class_id', $classIds)
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        // Discussions with optimized queries
        $recentDiscussions = Discussion::whereIn('course_id', $courseIds)
            ->orWhereIn('class_id', $classIds)
            ->with(['posts' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $recentDiscussionPosts = DiscussionPost::whereHas('discussion', function ($query) use ($courseIds, $classIds) {
            $query->whereIn('course_id', $courseIds)
                ->orWhereIn('class_id', $classIds);
        })->with(['author', 'discussion'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Announcements with optimized queries
        $recentAnnouncements = Announcement::whereIn('course_id', $courseIds)
            ->orWhereIn('class_id', $classIds)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Performance metrics
        $averageGrade = $gradedEnrollments > 0
            ? $myClasses->flatMap->enrollments->whereNotNull('final_grade')->avg('final_grade')
            : 0;

        $completionRate = $activeEnrollments > 0
            ? ($completedEnrollments / $activeEnrollments) * 100
            : 0;

        $classPerformance = [];
        foreach ($myClasses as $class) {
            $classGrades = $class->enrollments->whereNotNull('final_grade');
            $classPerformance[] = [
                'class' => $class,
                'average_grade' => $classGrades->isNotEmpty() ? $classGrades->avg('final_grade') : 0,
                'completion_rate' => $class->enrollments->isNotEmpty()
                    ? ($class->enrollments->where('status', 'completed')->count() / $class->enrollments->count()) * 100
                    : 0,
            ];
        }

        // At-risk students (low grades or low participation) with optimized query
        $atRiskStudents = Enrollment::whereIn('class_id', $classIds)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('final_grade', '<', 60)
                    ->orWhereNull('final_grade');
            })
            ->with(['student', 'class.course'])
            ->limit(10)
            ->get();

        // Recent enrollments with optimized query
        $recentEnrollments = Enrollment::whereHas('class', fn ($q) => $q->where('instructor_id', $instructorId))
            ->with(['student', 'class.course'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            // Academic Overview
            'my_courses' => $myCourses->count(),
            'my_classes' => $myClasses->count(),
            'total_students' => $totalStudents,
            'active_enrollments' => $activeEnrollments,
            'completed_enrollments' => $completedEnrollments,
            'graded_enrollments' => $gradedEnrollments,

            // Class Performance
            'average_grade' => $averageGrade,
            'completion_rate' => $completionRate,
            'class_performance' => $classPerformance,

            // Course Categories
            'published_courses' => $myCourses->where('status', 'published')->count(),
            'draft_courses' => $myCourses->where('status', 'draft')->count(),

            // Assignment Metrics
            'pending_submissions' => $pendingSubmissions,
            'upcoming_assignments' => $upcomingAssignments,
            'total_assignments' => Assignment::whereIn('class_id', $classIds)
                ->orWhereIn('course_id', $courseIds)
                ->count(),

            // Quiz Metrics
            'upcoming_quizzes' => $upcomingQuizzes,
            'recent_quiz_attempts' => $recentQuizAttempts,
            'total_quizzes' => Quiz::whereIn('class_id', $classIds)
                ->orWhereIn('course_id', $courseIds)
                ->count(),

            // Virtual Classes
            'upcoming_virtual_classes' => $upcomingVirtualClasses,
            'total_virtual_classes' => VirtualClass::whereIn('class_id', $classIds)->count(),

            // Discussions
            'recent_discussions' => $recentDiscussions,
            'recent_discussion_posts' => $recentDiscussionPosts,
            'total_discussions' => Discussion::whereIn('course_id', $courseIds)
                ->orWhereIn('class_id', $classIds)
                ->count(),

            // Announcements
            'recent_announcements' => $recentAnnouncements,
            'total_announcements' => Announcement::whereIn('course_id', $courseIds)
                ->orWhereIn('class_id', $classIds)
                ->count(),

            // Recent Activity
            'recent_enrollments' => $recentEnrollments,

            'my_classes_list' => $myClasses,
            'my_courses_list' => $myCourses,

            // At-risk indicators
            'at_risk_students' => $atRiskStudents,
            'at_risk_count' => $atRiskStudents->count(),
        ];
    }

    /**
     * Clear dashboard cache for the authenticated instructor
     */
    public function clearCache(): RedirectResponse
    {
        try {
            $instructorId = auth()->id();
            $cacheKey = "instructor_dashboard_{$instructorId}";
            Cache::forget($cacheKey);

            return redirect()->route('instructor.dashboard')
                ->with('success', 'Dashboard cache cleared successfully.');
        } catch (\Exception $e) {
            return redirect()->route('instructor.dashboard')
                ->with('error', 'Failed to clear cache: '.$e->getMessage());
        }
    }

    /**
     * Export dashboard data as CSV
     */
    public function exportData(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:students,grades,attendance,performance',
                'format' => 'required|in:csv,xlsx',
            ]);

            $instructorId = auth()->id();
            $stats = $this->calculateDashboardStats($instructorId);

            $filename = "instructor_dashboard_{$validated['type']}_".now()->format('Y-m-d');

            if ($validated['format'] === 'csv') {
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
                ];

                $callback = function () use ($validated, $stats) {
                    $file = fopen('php://output', 'w');

                    try {
                        switch ($validated['type']) {
                            case 'students':
                                fputcsv($file, ['Student Name', 'Class', 'Course', 'Status', 'Grade', 'Enrolled Date']);
                                foreach ($stats['my_classes_list'] as $class) {
                                    foreach ($class->enrollments as $enrollment) {
                                        fputcsv($file, [
                                            $enrollment->student->name ?? 'N/A',
                                            $class->code,
                                            $class->course->title ?? 'N/A',
                                            $enrollment->status,
                                            $enrollment->final_grade ?? 'N/A',
                                            $enrollment->enrolled_at?->format('Y-m-d') ?? 'N/A',
                                        ]);
                                    }
                                }
                                break;

                            case 'grades':
                                fputcsv($file, ['Class', 'Course', 'Average Grade', 'Completion Rate', 'Active Students']);
                                foreach ($stats['class_performance'] as $perf) {
                                    fputcsv($file, [
                                        $perf['class']->code,
                                        $perf['class']->course->title ?? 'N/A',
                                        number_format($perf['average_grade'], 2),
                                        number_format($perf['completion_rate'], 2),
                                        $perf['class']->enrollments->where('status', 'active')->count(),
                                    ]);
                                }
                                break;

                            case 'performance':
                                fputcsv($file, ['Metric', 'Value']);
                                fputcsv($file, ['Total Courses', $stats['my_courses']]);
                                fputcsv($file, ['Total Classes', $stats['my_classes']]);
                                fputcsv($file, ['Total Students', $stats['total_students']]);
                                fputcsv($file, ['Active Enrollments', $stats['active_enrollments']]);
                                fputcsv($file, ['Completed Enrollments', $stats['completed_enrollments']]);
                                fputcsv($file, ['Average Grade', number_format($stats['average_grade'], 2)]);
                                fputcsv($file, ['Completion Rate', number_format($stats['completion_rate'], 2)]);
                                fputcsv($file, ['At-Risk Students', $stats['at_risk_count']]);
                                break;

                            default:
                                fputcsv($file, ['No data available for this type']);
                        }
                    } catch (\Exception $e) {
                        fputcsv($file, ['Error', $e->getMessage()]);
                    } finally {
                        fclose($file);
                    }
                };

                return Response::stream($callback, 200, $headers);
            }

            return back()->with('error', 'Only CSV export is currently supported.');
        } catch (\Exception $e) {
            return back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    /**
     * Get real-time statistics (for AJAX requests)
     */
    public function getRealTimeStats()
    {
        try {
            $instructorId = auth()->id();
            $stats = $this->calculateDashboardStats($instructorId);

            return response()->json([
                'success' => true,
                'data' => [
                    'pending_submissions' => $stats['pending_submissions'],
                    'upcoming_assignments_count' => $stats['upcoming_assignments']->count(),
                    'upcoming_quizzes_count' => $stats['upcoming_quizzes']->count(),
                    'at_risk_count' => $stats['at_risk_count'],
                    'recent_enrollments_count' => $stats['recent_enrollments']->count(),
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
     * Search functionality for dashboard data
     */
    public function search(Request $request)
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string|min:2',
                'type' => 'required|in:all,students,courses,classes,assignments,quizzes',
            ]);

            $instructorId = auth()->id();
            $query = $validated['query'];
            $type = $validated['type'];

            $results = [];

            // When type is 'all', search across every category
            $searchTypes = $type === 'all' ? ['students', 'courses', 'classes', 'assignments', 'quizzes'] : [$type];

            foreach ($searchTypes as $searchType) {
                $results = array_merge($results, $this->searchType($searchType, $query, $instructorId));
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

    /**
     * Search a single entity type for dashboard search
     */
    private function searchType(string $type, string $query, int $instructorId): array
    {
        switch ($type) {
            case 'students':
                return User::whereHas('enrollments.class', fn ($q) => $q->where('instructor_id', $instructorId))
                    ->where(function ($q) use ($query) {
                        $q->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('email', 'like', "%{$query}%");
                    })
                    ->with('enrollments.class.course')
                    ->limit(10)
                    ->get()
                    ->map(fn ($user) => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'classes' => $user->enrollments->pluck('class.course.title')->toArray(),
                    ])
                    ->toArray();

            case 'courses':
                return Course::where('created_by', $instructorId)
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'like', "%{$query}%")
                            ->orWhere('code', 'like', "%{$query}%");
                    })
                    ->limit(10)
                    ->get()
                    ->map(fn ($course) => [
                        'id' => $course->id,
                        'title' => $course->title,
                        'code' => $course->code,
                        'status' => $course->status,
                    ])
                    ->toArray();

            case 'classes':
                return ClassModel::where('instructor_id', $instructorId)
                    ->where('code', 'like', "%{$query}%")
                    ->with('course')
                    ->limit(10)
                    ->get()
                    ->map(fn ($class) => [
                        'id' => $class->id,
                        'code' => $class->code,
                        'course_title' => $class->course->title ?? 'N/A',
                        'student_count' => $class->enrollments->count(),
                    ])
                    ->toArray();

            case 'assignments':
                $classIds = ClassModel::where('instructor_id', $instructorId)->pluck('id');

                return Assignment::whereIn('class_id', $classIds)
                    ->where('title', 'like', "%{$query}%")
                    ->with('class')
                    ->limit(10)
                    ->get()
                    ->map(fn ($assignment) => [
                        'id' => $assignment->id,
                        'title' => $assignment->title,
                        'class_code' => $assignment->class->code ?? 'N/A',
                        'due_date' => $assignment->due_date?->format('Y-m-d'),
                        'status' => $assignment->status,
                    ])
                    ->toArray();

            case 'quizzes':
                $classIds = ClassModel::where('instructor_id', $instructorId)->pluck('id');

                return Quiz::whereIn('class_id', $classIds)
                    ->where('title', 'like', "%{$query}%")
                    ->with('class')
                    ->limit(10)
                    ->get()
                    ->map(fn ($quiz) => [
                        'id' => $quiz->id,
                        'title' => $quiz->title,
                        'class_code' => $quiz->class->code ?? 'N/A',
                        'availability_from' => $quiz->availability_from?->format('Y-m-d'),
                        'status' => $quiz->status,
                    ])
                    ->toArray();

            default:
                return [];
        }
    }

    /**
     * Get detailed analytics data
     */
    public function getAnalytics(Request $request)
    {
        try {
            $validated = $request->validate([
                'period' => 'required|in:week,month,semester,year',
                'type' => 'required|in:enrollment,performance,attendance,engagement',
            ]);

            $instructorId = auth()->id();
            $period = $validated['period'];
            $type = $validated['type'];

            $stats = $this->calculateDashboardStats($instructorId);

            $analytics = [];

            switch ($type) {
                case 'enrollment':
                    $analytics = [
                        'total_enrollments' => $stats['active_enrollments'],
                        'completed_enrollments' => $stats['completed_enrollments'],
                        'completion_rate' => $stats['completion_rate'],
                        'at_risk_students' => $stats['at_risk_count'],
                        'recent_enrollments' => $stats['recent_enrollments']->map(fn ($e) => [
                            'date' => $e->enrolled_at?->format('Y-m-d'),
                            'student' => $e->student->name,
                            'course' => $e->class->course->title,
                        ])->toArray(),
                    ];
                    break;

                case 'performance':
                    $analytics = [
                        'average_grade' => $stats['average_grade'],
                        'class_performance' => $stats['class_performance'],
                        'graded_enrollments' => $stats['graded_enrollments'],
                        'grade_distribution' => $this->calculateGradeDistribution($stats['my_classes_list']),
                    ];
                    break;

                case 'attendance':
                    $analytics = [
                        'total_classes' => $stats['my_classes'],
                        'average_attendance_rate' => $this->calculateAverageAttendance($stats['my_classes_list']),
                        'attendance_by_class' => $this->calculateAttendanceByClass($stats['my_classes_list']),
                    ];
                    break;

                case 'engagement':
                    $analytics = [
                        'total_discussions' => $stats['total_discussions'],
                        'recent_discussion_posts' => $stats['recent_discussion_posts']->count(),
                        'total_announcements' => $stats['total_announcements'],
                        'virtual_classes_held' => $stats['total_virtual_classes'],
                        'student_participation_rate' => $this->calculateParticipationRate($stats['my_classes_list']),
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
     * Calculate grade distribution
     */
    private function calculateGradeDistribution($classes): array
    {
        $distribution = [
            'A' => 0,
            'B' => 0,
            'C' => 0,
            'D' => 0,
            'F' => 0,
        ];

        foreach ($classes as $class) {
            foreach ($class->enrollments as $enrollment) {
                if ($enrollment->final_grade !== null) {
                    if ($enrollment->final_grade >= 90) {
                        $distribution['A']++;
                    } elseif ($enrollment->final_grade >= 80) {
                        $distribution['B']++;
                    } elseif ($enrollment->final_grade >= 70) {
                        $distribution['C']++;
                    } elseif ($enrollment->final_grade >= 60) {
                        $distribution['D']++;
                    } else {
                        $distribution['F']++;
                    }
                }
            }
        }

        return $distribution;
    }

    /**
     * Calculate average attendance rate
     */
    private function calculateAverageAttendance($classes): float
    {
        $totalPresent = 0;
        $totalRecords = 0;

        foreach ($classes as $class) {
            $records = AttendanceRecord::where('class_id', $class->id)->get();
            $totalRecords += $records->count();
            $totalPresent += $records->where('status', 'present')->count();
        }

        return $totalRecords > 0 ? ($totalPresent / $totalRecords) * 100 : 0;
    }

    /**
     * Calculate attendance by class
     */
    private function calculateAttendanceByClass($classes): array
    {
        $attendanceData = [];

        foreach ($classes as $class) {
            $records = AttendanceRecord::where('class_id', $class->id)->get();
            $presentCount = $records->where('status', 'present')->count();
            $totalRecords = $records->count();

            $attendanceData[] = [
                'class_code' => $class->code,
                'course_title' => $class->course->title ?? 'N/A',
                'attendance_rate' => $totalRecords > 0 ? ($presentCount / $totalRecords) * 100 : 0,
                'total_records' => $totalRecords,
            ];
        }

        return $attendanceData;
    }

    /**
     * Calculate student participation rate
     */
    private function calculateParticipationRate($classes): float
    {
        $totalStudents = 0;
        $activeParticipants = 0;

        foreach ($classes as $class) {
            $totalStudents += $class->enrollments->count();
            $activeParticipants += $class->enrollments->where('status', 'active')->count();
        }

        return $totalStudents > 0 ? ($activeParticipants / $totalStudents) * 100 : 0;
    }
}
