<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Models\VirtualClass;
use App\Services\GradeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private GradeService $grades)
    {
    }

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
        }

        // Real grades per class, computed from released + graded grade items.
        // `enrollments.final_grade` is only populated when an instructor manually
        // completes an enrollment, so it cannot be used as a grade source.
        //
        // Scope to ACTIVE students only. Previously every enrollment was
        // summarised regardless of status, so a class whose students had all
        // been dropped still showed an average grade while the row reported
        // "0 students".
        $classSummaries = [];

        foreach ($myClasses as $class) {
            $activeStudentIds = $class->enrollments
                ->where('status', 'active')
                ->pluck('student_id')
                ->all();

            $classSummaries[$class->id] = $this->grades->computeClassGradeSummaries(
                $class->id,
                $activeStudentIds
            );

            $gradedEnrollments += collect($classSummaries[$class->id])->where('is_graded', true)->count();
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

        // Exam metrics with optimized queries
        $upcomingExams = Exam::whereHas('class', function ($query) use ($classIds) {
            $query->whereIn('id', $classIds);
        })->where('status', 'published')
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        $recentExamAttempts = ExamAttempt::whereHas('exam', function ($query) use ($classIds, $courseIds) {
            $query->whereIn('class_id', $classIds)
                ->orWhereIn('course_id', $courseIds);
        })->with(['student', 'exam'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Virtual classes with optimized queries
        $upcomingVirtualClasses = VirtualClass::whereIn('class_id', $classIds)
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        // Combine upcoming activities
        $upcomingActivities = collect();
        foreach ($upcomingAssignments as $assignment) {
            $courseId = $assignment->resolveCourseId();
            $upcomingActivities->push([
                'title' => $assignment->title,
                'type' => 'Assignment',
                'date' => $assignment->due_date->format('M d, Y g:i A'),
                'url' => $courseId ? route('instructor.courses.assignments.show', [$courseId, $assignment]) : '#',
            ]);
        }
        foreach ($upcomingQuizzes as $quiz) {
            $courseId = $quiz->resolveCourseId();
            $upcomingActivities->push([
                'title' => $quiz->title,
                'type' => 'Quiz',
                'date' => $quiz->availability_from->format('M d, Y g:i A'),
                'url' => $courseId ? route('instructor.courses.quizzes.show', [$courseId, $quiz]) : '#',
            ]);
        }
        foreach ($upcomingExams as $exam) {
            $courseId = $exam->course_id;
            $upcomingActivities->push([
                'title' => $exam->title,
                'type' => 'Exam',
                'date' => $exam->starts_at->format('M d, Y g:i A'),
                'url' => $courseId ? route('instructor.courses.exams.show', [$courseId, $exam]) : '#',
            ]);
        }
        foreach ($upcomingVirtualClasses as $virtualClass) {
            $upcomingActivities->push([
                'title' => $virtualClass->title,
                'type' => 'Virtual Class',
                'date' => $virtualClass->meeting_date->format('M d, Y').' '.$virtualClass->start_time,
                'url' => route('instructor.classes.virtual_classes.show', [$virtualClass->class, $virtualClass]),
            ]);
        }
        $upcomingActivities = $upcomingActivities->sortBy('date')->take(5);

        // Announcements with optimized queries
        $recentAnnouncements = Announcement::whereIn('course_id', $courseIds)
            ->orWhereIn('class_id', $classIds)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Performance metrics — derived from real released grade items.
        $allGradedPercents = collect($classSummaries)
            ->flatMap(fn ($summaries) => collect($summaries)->where('is_graded', true))
            ->pluck('percent');

        $averageGrade = $allGradedPercents->isNotEmpty() ? $allGradedPercents->avg() : 0;

        $activeRows = 0;
        $progressSum = 0.0;

        foreach ($myClasses as $class) {
            foreach ($class->enrollments->where('status', 'active') as $enrollment) {
                $course = $class->course;
                if (! $course) {
                    continue;
                }

                $live = app(\App\Services\ContentProgressService::class)
                    ->calculateCourseLiveProgress($course, $enrollment->student_id, $enrollment->class_id);

                $progressSum += $live['overall'];
                $activeRows++;
            }
        }

        // Global completion/completion rate is the average live course progress
        // across the instructor's enrolled students, not "how many classes exist".
        $completionRate = $activeRows > 0
            ? $progressSum / $activeRows
            : 0;

        $classPerformance = [];
        foreach ($myClasses as $class) {
            $classGraded = collect($classSummaries[$class->id])->where('is_graded', true);

            $classLiveAvg = 0.0;
            $classLiveCount = 0;

            foreach ($class->enrollments->where('status', 'active') as $enrollment) {
                $course = $class->course;
                if (! $course) {
                    continue;
                }

                $live = app(\App\Services\ContentProgressService::class)
                    ->calculateCourseLiveProgress($course, $enrollment->student_id, $enrollment->class_id);

                $classLiveAvg += $live['overall'];
                $classLiveCount++;
            }

            $classPerformance[] = [
                'class' => $class,
                'average_grade' => $classGraded->isNotEmpty() ? (float) $classGraded->avg('percent') : 0,
                'completion_rate' => $classLiveCount > 0 ? round($classLiveAvg / $classLiveCount, 1) : 0,
                'graded_students' => $classGraded->count(),
                'active_students' => $class->enrollments->where('status', 'active')->count(),
                'has_students' => $classLiveCount > 0,
            ];
        }

        // At-risk = a released grade exists AND it is below the passing mark.
        // Previously this flagged every student because final_grade is almost
        // always NULL, which made the count meaningless.
        $passingGrade = (float) config('lms.passing_grade', 60);
        $atRiskEnrollments = collect();

        foreach ($myClasses as $class) {
            $summaries = $classSummaries[$class->id];

            $atRiskIds = collect($summaries)
                ->filter(fn ($s) => $s['is_graded'] && $s['percent'] < $passingGrade)
                ->keys();

            if ($atRiskIds->isEmpty()) {
                continue;
            }

            Enrollment::where('class_id', $class->id)
                ->where('status', 'active')
                ->whereIn('student_id', $atRiskIds)
                ->get()
                ->each(function ($enrollment) use ($atRiskEnrollments, $summaries) {
                    $enrollment->loadMissing(['student', 'class.course']);
                    $enrollment->computed_percent = $summaries[$enrollment->student_id]['percent'] ?? null;
                    $atRiskEnrollments->push($enrollment);
                });
        }

        $atRiskStudents = $atRiskEnrollments->take(10)->values();

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
            'class_summaries' => $classSummaries,

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

            // Exam Metrics
            'upcoming_exams' => $upcomingExams,
            'recent_exam_attempts' => $recentExamAttempts,
            'total_exams' => Exam::whereIn('class_id', $classIds)
                ->orWhereIn('course_id', $courseIds)
                ->count(),

            // Virtual Classes
            'upcoming_virtual_classes' => $upcomingVirtualClasses,
            'total_virtual_classes' => VirtualClass::whereIn('class_id', $classIds)->count(),

            // Announcements
            'recent_announcements' => $recentAnnouncements,
            'total_announcements' => Announcement::whereIn('course_id', $courseIds)
                ->orWhereIn('class_id', $classIds)
                ->count(),

            // Recent Activity
            'recent_enrollments' => $recentEnrollments,

            // Upcoming Activities (combined)
            'upcoming_activities' => $upcomingActivities,

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
                                fputcsv($file, ['Student Name', 'Class', 'Course', 'Status', 'Grade %', 'Letter', 'Enrolled Date']);
                                foreach ($stats['my_classes_list'] as $class) {
                                    $summaries = $stats['class_summaries'][$class->id] ?? [];

                                    foreach ($class->enrollments as $enrollment) {
                                        $summary = $summaries[$enrollment->student_id] ?? null;

                                        fputcsv($file, [
                                            $enrollment->student?->name ?? 'N/A',
                                            $class->code,
                                            $class->course?->title ?? 'N/A',
                                            $enrollment->status,
                                            ($summary['is_graded'] ?? false) ? number_format($summary['percent'], 2) : 'N/A',
                                            ($summary['is_graded'] ?? false) ? $summary['letter_grade'] : 'N/A',
                                            $enrollment->enrolled_at?->format('Y-m-d') ?? 'N/A',
                                        ]);
                                    }
                                }
                                break;

                            case 'grades':
                                fputcsv($file, ['Class', 'Course', 'Average Grade', 'Graded Students', 'Completion Rate', 'Active Students']);
                                foreach ($stats['class_performance'] as $perf) {
                                    fputcsv($file, [
                                        $perf['class']->code,
                                        $perf['class']->course?->title ?? 'N/A',
                                        number_format($perf['average_grade'], 2),
                                        $perf['graded_students'],
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
                                fputcsv($file, ['Graded Enrollments', $stats['graded_enrollments']]);
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
                'type' => 'required|in:all,students,courses,classes,modules,lessons,assignments,quizzes,grades',
            ]);

            $instructorId = auth()->id();
            $query = $validated['query'];
            $type = $validated['type'];

            $results = [];

            // When type is 'all', search across every category
            $searchTypes = $type === 'all' ? ['grades', 'students', 'courses', 'classes', 'modules', 'lessons', 'assignments', 'quizzes'] : [$type];

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

            case 'modules':
                $courseIds = auth()->user()->classesInstructing()->pluck('course_id')->merge(
                    auth()->user()->coursesCreated()->pluck('id')
                )->unique();

                return Module::whereIn('course_id', $courseIds)
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    })
                    ->with('course')
                    ->limit(10)
                    ->get()
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'course_id' => $m->course_id,
                        'entity' => 'module',
                        'title' => $m->title,
                        'course_title' => $m->course?->title ?? '',
                    ])
                    ->toArray();

            case 'lessons':
                $courseIds = auth()->user()->classesInstructing()->pluck('course_id')->merge(
                    auth()->user()->coursesCreated()->pluck('id')
                )->unique();

                return Lesson::whereHas('module', fn ($q) => $q->whereIn('course_id', $courseIds))
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    })
                    ->with('module.course')
                    ->limit(10)
                    ->get()
                    ->map(fn ($l) => [
                        'id' => $l->id,
                        'course_id' => $l->module?->course_id,
                        'entity' => 'lesson',
                        'title' => $l->title,
                        'course_title' => $l->module?->course?->title ?? '',
                    ])
                    ->toArray();

            case 'grades':
                $classIds = ClassModel::where('instructor_id', $instructorId)->pluck('id');
                $gradeItemIds = GradeItem::whereIn('class_id', $classIds)->pluck('id');

                return Grade::with(['student', 'item'])
                    ->whereIn('grade_item_id', $gradeItemIds)
                    ->where(function ($q) use ($query) {
                        $q->whereHas('item', fn ($q) => $q->where('title', 'like', "%{$query}%"))
                            ->orWhereHas('student', function ($q) use ($query) {
                                $q->where('first_name', 'like', "%{$query}%")
                                    ->orWhere('last_name', 'like', "%{$query}%");
                            })
                            ->orWhere('letter_grade', 'like', "%{$query}%")
                            ->orWhere('score_percent', 'like', "%{$query}%");
                    })
                    ->limit(10)
                    ->get()
                    ->map(fn ($g) => [
                        'id' => $g->id,
                        'student_id' => $g->student_id,
                        'entity' => 'grade',
                        'name' => trim(($g->student?->first_name ?? '').' '.($g->student?->last_name ?? '')),
                        'item_name' => $g->item?->title ?? 'Grade',
                        'letter_grade' => $g->letter_grade,
                        'score_percent' => $g->score_percent,
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
                'type' => 'required|in:enrollment,performance,attendance,engagement,quizzes,exams,assignments',
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
                        'average_grade' => (float) $stats['average_grade'],
                        'class_performance' => collect($stats['class_performance'])->map(fn ($p) => [
                            'class' => $p['class']->code,
                            'course' => $p['class']->course?->title ?? 'N/A',
                            'average_grade' => (float) $p['average_grade'],
                            'completion_rate' => (float) $p['completion_rate'],
                            'graded_students' => $p['graded_students'],
                            'active_students' => $p['active_students'],
                        ])->all(),
                        'graded_enrollments' => $stats['graded_enrollments'],
                        'grade_distribution' => $this->calculateGradeDistribution($stats['class_summaries']),
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
                        'total_announcements' => $stats['total_announcements'],
                        'virtual_classes_held' => $stats['total_virtual_classes'],
                        'student_participation_rate' => $this->calculateParticipationRate($stats['my_classes_list']),
                    ];
                    break;

                case 'quizzes':
                    $analytics = [
                        'total_quizzes' => $stats['total_quizzes'],
                        'upcoming_quizzes' => $stats['upcoming_quizzes']->count(),
                        'recent_attempts' => $stats['recent_quiz_attempts']->count(),
                        'average_quiz_score' => $this->calculateAverageQuizScore($stats['my_classes_list']),
                        'quiz_completion_rate' => $this->calculateQuizCompletionRate($stats['my_classes_list']),
                    ];
                    break;

                case 'exams':
                    $analytics = [
                        'total_exams' => $stats['total_exams'],
                        'upcoming_exams' => $stats['upcoming_exams']->count(),
                        'recent_attempts' => $stats['recent_exam_attempts']->count(),
                        'average_exam_score' => $this->calculateAverageExamScore($stats['my_classes_list']),
                        'exam_completion_rate' => $this->calculateExamCompletionRate($stats['my_classes_list']),
                    ];
                    break;

                case 'assignments':
                    $analytics = [
                        'total_assignments' => $stats['total_assignments'],
                        'pending_submissions' => $stats['pending_submissions'],
                        'upcoming_assignments' => $stats['upcoming_assignments']->count(),
                        'average_assignment_score' => $this->calculateAverageAssignmentScore($stats['my_classes_list']),
                        'assignment_submission_rate' => $this->calculateAssignmentSubmissionRate($stats['my_classes_list']),
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
     * Calculate grade distribution from computed (released + graded) results.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $classSummaries
     */
    private function calculateGradeDistribution(array $classSummaries): array
    {
        $distribution = [
            'A' => 0,
            'B' => 0,
            'C' => 0,
            'D' => 0,
            'F' => 0,
        ];

        $graded = collect($classSummaries)
            ->flatMap(fn ($summaries) => collect($summaries)->where('is_graded', true));

        foreach ($graded as $summary) {
            $distribution[$summary['letter_grade']] = ($distribution[$summary['letter_grade']] ?? 0) + 1;
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

    /**
     * Calculate average quiz score
     */
    private function calculateAverageQuizScore($classes): float
    {
        $classIds = $classes->pluck('id');
        $attempts = QuizAttempt::whereHas('quiz', function ($query) use ($classIds) {
            $query->whereIn('class_id', $classIds);
        })->where('status', 'graded')->get();

        return $attempts->isNotEmpty() ? $attempts->avg('score_percent') : 0;
    }

    /**
     * Calculate quiz completion rate
     */
    private function calculateQuizCompletionRate($classes): float
    {
        $classIds = $classes->pluck('id');
        $totalQuizzes = Quiz::whereIn('class_id', $classIds)->count();
        $totalAttempts = QuizAttempt::whereHas('quiz', function ($query) use ($classIds) {
            $query->whereIn('class_id', $classIds);
        })->count();

        return $totalQuizzes > 0 ? ($totalAttempts / $totalQuizzes) : 0;
    }

    /**
     * Calculate average exam score
     */
    private function calculateAverageExamScore($classes): float
    {
        $classIds = $classes->pluck('id');
        $attempts = ExamAttempt::whereHas('exam', function ($query) use ($classIds) {
            $query->whereIn('class_id', $classIds);
        })->where('status', 'graded')->get();

        return $attempts->isNotEmpty() ? $attempts->avg('score_percent') : 0;
    }

    /**
     * Calculate exam completion rate
     */
    private function calculateExamCompletionRate($classes): float
    {
        $classIds = $classes->pluck('id');
        $totalExams = Exam::whereIn('class_id', $classIds)->count();
        $totalAttempts = ExamAttempt::whereHas('exam', function ($query) use ($classIds) {
            $query->whereIn('class_id', $classIds);
        })->count();

        return $totalExams > 0 ? ($totalAttempts / $totalExams) : 0;
    }

    /**
     * Calculate average assignment score
     */
    private function calculateAverageAssignmentScore($classes): float
    {
        $classIds = $classes->pluck('id');
        $submissions = AssignmentSubmission::whereHas('assignment', function ($query) use ($classIds) {
            $query->whereIn('class_id', $classIds);
        })->where('status', 'graded')->get();

        if ($submissions->isEmpty()) {
            return 0;
        }

        $totalScore = 0;
        $count = 0;
        foreach ($submissions as $submission) {
            $grade = $submission->grade;
            if ($grade && $grade->score_percent !== null) {
                $totalScore += $grade->score_percent;
                $count++;
            }
        }

        return $count > 0 ? $totalScore / $count : 0;
    }

    /**
     * Calculate assignment submission rate
     */
    private function calculateAssignmentSubmissionRate($classes): float
    {
        $classIds = $classes->pluck('id');
        $totalAssignments = Assignment::whereIn('class_id', $classIds)->count();
        $totalSubmissions = AssignmentSubmission::whereHas('assignment', function ($query) use ($classIds) {
            $query->whereIn('class_id', $classIds);
        })->count();

        return $totalAssignments > 0 ? ($totalSubmissions / $totalAssignments) : 0;
    }
}
