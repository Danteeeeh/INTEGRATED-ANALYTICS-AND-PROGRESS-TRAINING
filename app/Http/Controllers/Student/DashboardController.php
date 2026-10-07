<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentExtension;
use App\Models\ClassModel;
use App\Models\CourseCompletion;
use App\Models\CourseProgress;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamExtension;
use App\Models\Feedback;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizExtension;
use App\Models\VirtualClass;
use App\Services\ContentProgressService;
use App\Services\StudentPerformanceAssessmentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private StudentPerformanceAssessmentService $performanceAssessment,
        private \App\Services\ContentProgressService $contentProgress,
    ) {}

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

        // Calculate week from now for upcoming items
        $weekFromNow = now()->addDays(7);

        // Upcoming assignments (due within the next 7 days, not yet submitted)
        $upcomingAssignments = $this->contentProgress->studentAssignmentsQuery($classIds, $courseIds)
            ->whereDoesntHave('submissions', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->where('due_date', '>', now())
            ->where('due_date', '<=', $weekFromNow)
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Overdue assignments (considering extensions)
        $allAssignments = $this->contentProgress->studentAssignmentsQuery($classIds, $courseIds)
            ->whereDoesntHave('submissions', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->with(['class.course', 'module.course', 'lesson.module.course'])
            ->get();

        $overdueAssignments = $allAssignments->filter(function ($assignment) use ($studentId) {
            $effectiveDeadline = $assignment->getEffectiveDeadlineForStudent($studentId);
            return $effectiveDeadline && $effectiveDeadline->isPast();
        })->take(5);

        // Overdue quizzes (considering extensions)
        $allQuizzes = $this->contentProgress->studentQuizzesQuery($classIds, $courseIds)
            ->whereDoesntHave('attempts', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->with(['class.course', 'module.course', 'lesson.module.course'])
            ->get();

        $overdueQuizzes = $allQuizzes->filter(function ($quiz) use ($studentId) {
            $effectiveDeadline = $quiz->getEffectiveDeadlineForStudent($studentId);
            return $effectiveDeadline && $effectiveDeadline->isPast();
        })->take(5);

        // Overdue exams (considering extensions)
        $allExams = $this->contentProgress->studentExamsQuery($classIds, $courseIds)
            ->whereDoesntHave('attempts', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->with(['class.course'])
            ->get();

        $overdueExams = $allExams->filter(function ($exam) use ($studentId) {
            $effectiveDeadline = $exam->getEffectiveDeadlineForStudent($studentId);
            return $effectiveDeadline && $effectiveDeadline->isPast();
        })->take(5);

        // Upcoming quizzes (available now, opening soon, or due within the next 7 days)
        $upcomingQuizzes = $this->contentProgress->studentQuizzesQuery($classIds, $courseIds)
            ->whereDoesntHave('attempts', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->where(function ($query) use ($weekFromNow) {
                // Quizzes currently available (opened and not expired)
                $query->where('availability_from', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('availability_until')
                            ->orWhere('availability_until', '>', now());
                    });
                // Quizzes opening within the next 7 days
                $query->orWhere(function ($q) use ($weekFromNow) {
                    $q->where('availability_from', '>', now())
                        ->where('availability_from', '<=', $weekFromNow);
                });
            })
            ->orderBy('availability_from')
            ->limit(5)
            ->get();

        // Recent quiz attempts
        $recentQuizAttempts = QuizAttempt::where('student_id', $studentId)
            ->with(['quiz.class', 'quiz.module.course', 'quiz.lesson.module.course'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Upcoming exams (available now, starting soon, or ending within the next 7 days)
        $upcomingExams = $this->contentProgress->studentExamsQuery($classIds, $courseIds)
            ->whereDoesntHave('attempts', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->where(function ($query) use ($weekFromNow) {
                // Exams currently available (started and not ended)
                $query->where('starts_at', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('ends_at')
                            ->orWhere('ends_at', '>', now());
                    });
                // Exams starting within the next 7 days
                $query->orWhere(function ($q) use ($weekFromNow) {
                    $q->where('starts_at', '>', now())
                        ->where('starts_at', '<=', $weekFromNow);
                });
            })
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        // Recent exam attempts
        $recentExamAttempts = ExamAttempt::where('student_id', $studentId)
            ->with(['exam.class'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Upcoming virtual classes (within the next 7 days)
        $upcomingVirtualClasses = VirtualClass::whereIn('class_id', $classIds)
            ->where(function ($query) use ($weekFromNow) {
                // Virtual classes in the next 7 days
                $query->whereDate('meeting_date', '>', today())
                    ->whereDate('meeting_date', '<=', $weekFromNow);
                // Virtual classes today that haven't started yet
                $query->orWhere(function ($sameDay) {
                    $sameDay->whereDate('meeting_date', today())
                        ->whereTime('start_time', '>', now()->format('H:i:s'));
                });
            })
            ->with('class.course')
            ->orderBy('meeting_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        // Recent grades — only for classes the student is enrolled in
        $recentGrades = Grade::where('student_id', $studentId)
            ->with('item.class.course')
            ->whereHas('item', fn ($q) => $q->where('is_released', true)
                ->whereIn('class_id', $classIds))
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
                $liveProgress = $this->contentProgress->calculateCourseLiveProgress($course, $studentId, $enrollment->class_id);
                $persisted = CourseProgress::where('student_id', $studentId)
                    ->where('class_id', $enrollment->class_id)
                    ->first();

                $courseProgressData[] = [
                    'course' => $course,
                    'progress' => $liveProgress['overall'],
                    'breakdown' => $liveProgress,
                    'last_accessed' => $persisted?->updated_at,
                ];

                $overallProgress += $liveProgress['overall'];
            }
        }

        $overallProgress = $activeEnrollments->isNotEmpty() ? $overallProgress / $activeEnrollments->count() : 0;

        // Continue learning (most recent course with progress by last accessed)
        $continueLearning = collect($courseProgressData)
            ->sortByDesc('last_accessed')
            ->first();

        // Announcements for student's courses
        $announcements = Announcement::where(function ($q) use ($courseIds, $classIds) {
            $q->whereIn('course_id', $courseIds)
                ->orWhereIn('class_id', $classIds);
        })
            ->where('publish_at', '<=', now())
            ->orderBy('publish_at', 'desc')
            ->limit(5)
            ->get();

        // Combine upcoming activities
        $upcomingActivities = collect();
        foreach ($upcomingAssignments as $assignment) {
            $upcomingActivities->push([
                'title' => $assignment->title,
                'type' => 'Assignment',
                'date' => $assignment->due_date ? $assignment->due_date->format('M d, Y g:i A') : 'No due date',
                'date_obj' => $assignment->due_date ?? now()->addYears(100),
                'url' => $assignment->class_id ? route('student.courses.assignments.show', [$assignment->class->course, $assignment]) : '#',
            ]);
        }
        foreach ($upcomingQuizzes as $quiz) {
            $upcomingActivities->push([
                'title' => $quiz->title,
                'type' => 'Quiz',
                'date' => $quiz->availability_from ? $quiz->availability_from->format('M d, Y g:i A') : 'Available now',
                'date_obj' => $quiz->availability_from ?? now(),
                'url' => $quiz->class_id ? route('student.courses.quizzes.show', [$quiz->class->course, $quiz]) : '#',
            ]);
        }
        foreach ($upcomingExams as $exam) {
            $upcomingActivities->push([
                'title' => $exam->title,
                'type' => 'Exam',
                'date' => $exam->starts_at ? $exam->starts_at->format('M d, Y g:i A') : 'Available now',
                'date_obj' => $exam->starts_at ?? now(),
                'url' => $exam->class_id ? route('student.courses.exams.show', [$exam->class->course, $exam]) : '#',
            ]);
        }
        foreach ($upcomingVirtualClasses as $virtualClass) {
            $upcomingActivities->push([
                'title' => $virtualClass->title,
                'type' => 'Virtual Class',
                'date' => $virtualClass->meeting_date->format('M d, Y').' '.$virtualClass->start_time,
                'date_obj' => $virtualClass->meeting_date,
                'url' => route('student.classes.virtual_classes.show', [$virtualClass->class, $virtualClass]),
            ]);
        }
        $upcomingActivities = $upcomingActivities->sortBy('date_obj')->map(function ($item) {
            unset($item['date_obj']);
            return $item;
        })->take(5);

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
            'total_assignments' => $this->contentProgress->studentAssignmentsQuery($classIds, $courseIds)->count(),

            // Quizzes
            'upcoming_quizzes' => $upcomingQuizzes,
            'overdue_quizzes' => $overdueQuizzes,
            'recent_quiz_attempts' => $recentQuizAttempts,
            'total_quizzes' => $this->contentProgress->studentQuizzesQuery($classIds, $courseIds)->count(),

            // Exams
            'upcoming_exams' => $upcomingExams,
            'overdue_exams' => $overdueExams,
            'recent_exam_attempts' => $recentExamAttempts,
            'total_exams' => $this->contentProgress->studentExamsQuery($classIds, $courseIds)->count(),

            // Virtual Classes
            'upcoming_virtual_classes' => $upcomingVirtualClasses,
            'total_virtual_classes' => VirtualClass::whereIn('class_id', $classIds)
                ->where('status', 'published')
                ->whereDate('meeting_date', '>=', today())
                ->count(),

            // Grades and Feedback
            'recent_grades' => $recentGrades,
            'recent_feedback' => $recentFeedback,
            'total_grades' => Grade::where('student_id', $studentId)
                ->whereHas('item', fn ($q) => $q->whereIn('class_id', $classIds))
                ->count(),

            // Communication
            'announcements' => $announcements,
            'total_announcements' => Announcement::where(function ($q) use ($courseIds, $classIds) {
                $q->whereIn('course_id', $courseIds)->orWhereIn('class_id', $classIds);
            })
                ->where('publish_at', '<=', now())
                ->count(),

            // Engagement
            'learning_streak' => $learningStreak,
            'performance_assessment' => $performanceAssessment,

            // Upcoming Activities (combined)
            'upcoming_activities' => $upcomingActivities,

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
                'type' => 'required|in:all,courses,modules,lessons,assignments,quizzes,grades',
            ]);

            $studentId = auth()->id();
            $query = $validated['query'];
            $type = $validated['type'];
            $results = [];
            $types = $type === 'all' ? ['courses', 'modules', 'lessons', 'assignments', 'quizzes', 'grades'] : [$type];

            foreach ($types as $t) {
                $results = array_merge($results, $this->searchType($t, $query, $studentId));
            }

            return response()->json([
                'success' => true,
                'data' => array_slice($results, 0, 30),
            ]);
        } catch (ValidationException $e) {
            // A missing/bad parameter is the caller's fault, not a server
            // fault: let Laravel answer 422 instead of masking it as a 500.
            throw $e;
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
        $courseIds = ClassModel::whereIn('id', $classIds)->pluck('course_id');

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
                return $this->contentProgress->studentAssignmentsQuery($classIds, $courseIds)
                    ->where('title', 'like', "%{$query}%")
                    ->with(['class.course', 'module.course', 'lesson.module.course'])
                    ->limit(10)
                    ->get()
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'title' => $a->title,
                        'class_code' => $a->class?->code ?? '',
                        'course_title' => ($a->class?->course ?? $a->module?->course ?? $a->lesson?->module?->course)?->title ?? '',
                        'due_date' => $a->due_date?->format('Y-m-d'),
                        'status' => $a->status,
                    ])
                    ->toArray();

            case 'quizzes':
                return $this->contentProgress->studentQuizzesQuery($classIds, $courseIds)
                    ->where('title', 'like', "%{$query}%")
                    ->with(['class.course', 'module.course', 'lesson.module.course'])
                    ->limit(10)
                    ->get()
                    ->map(fn ($q) => [
                        'id' => $q->id,
                        'title' => $q->title,
                        'class_code' => $q->class?->code ?? '',
                        'course_title' => ($q->class?->course ?? $q->module?->course ?? $q->lesson?->module?->course)?->title ?? '',
                        'availability_from' => $q->availability_from?->format('Y-m-d'),
                        'status' => $q->status,
                    ])
                    ->toArray();

            case 'modules':
                $courseIds = ClassModel::whereIn('id', $classIds)->pluck('course_id');

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
                $courseIds = ClassModel::whereIn('id', $classIds)->pluck('course_id');

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
                $classIds = Enrollment::where('student_id', $studentId)
                    ->where('status', 'active')
                    ->pluck('class_id');

                return Grade::where('student_id', $studentId)
                    ->with(['item'])
                    ->whereHas('item', fn ($q) => $q->whereIn('class_id', $classIds))
                    ->where(function ($q) use ($query) {
                        $q->whereHas('item', fn ($q) => $q->where('title', 'like', "%{$query}%"))
                            ->orWhere('letter_grade', 'like', "%{$query}%")
                            ->orWhere('score_percent', 'like', "%{$query}%");
                    })
                    ->limit(10)
                    ->get()
                    ->map(fn ($g) => [
                        'id' => $g->id,
                        'entity' => 'grade',
                        'name' => $g->item?->title ?? 'Grade',
                        'course_title' => $g->item?->class?->course?->title ?? '',
                        'class_code' => $g->item?->class?->code ?? '',
                        'letter_grade' => $g->letter_grade,
                        'score_percent' => $g->score_percent,
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
                    $classIds = Enrollment::where('student_id', $studentId)
                        ->where('status', 'active')
                        ->pluck('class_id');

                    $grades = Grade::where('student_id', $studentId)
                        ->with('item.class.course')
                        ->whereHas('item', fn ($q) => $q->where('is_released', true)
                            ->whereIn('class_id', $classIds))
                        ->orderBy('graded_at')
                        ->get()
                        ->map(fn ($g) => [
                            'name' => $g->item?->title ?? 'Grade',
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
                        ->whereIn('status', [
                            QuizAttempt::STATUS_SUBMITTED,
                            QuizAttempt::STATUS_AUTO_SUBMITTED,
                            QuizAttempt::STATUS_GRADED,
                        ])
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
                        $course = $enrollment->class->course;
                        if (! $course) {
                            continue;
                        }
                        $live = $this->contentProgress->calculateCourseLiveProgress($course, $studentId, $enrollment->class_id);
                        $progressData[] = [
                            'course' => $course->title ?? 'Course',
                            'progress' => round($live['overall'] ?? 0, 1),
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
        } catch (ValidationException $e) {
            // Surface a 422 with the error bag instead of a misleading 500.
            throw $e;
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
        $courseIds = $activeEnrollments->pluck('class.course_id')->filter();

        $upcomingAssignments = $this->contentProgress->studentAssignmentsQuery($classIds, $courseIds)
            ->where('due_date', '>', now())
            ->orderBy('due_date')->limit(5)->get();

        $upcomingQuizzes = $this->contentProgress->studentQuizzesQuery($classIds, $courseIds)
            ->where('availability_from', '>', now())
            ->orderBy('availability_from')->limit(5)->get();

        $overdueAssignments = $this->contentProgress->studentAssignmentsQuery($classIds, $courseIds)
            ->where('due_date', '<', now())
            ->whereDoesntHave('submissions', fn ($q) => $q->where('student_id', $studentId))
            ->orderBy('due_date', 'desc')->limit(5)->get();

        $recentGrades = Grade::where('student_id', $studentId)
            ->with('item.class.course')
            ->whereHas('item', fn ($q) => $q->where('is_released', true)
                ->whereIn('class_id', $classIds))
            ->orderBy('graded_at', 'desc')->limit(5)->get();

        $overallProgress = 0;
        $courseProgressCount = 0;
        foreach ($activeEnrollments as $enrollment) {
            $course = $enrollment->class->course;
            if ($course) {
                $liveProgress = $this->contentProgress->calculateCourseLiveProgress($course, $studentId, $enrollment->class_id);
                $overallProgress += $liveProgress['overall'];
                $courseProgressCount++;
            }
        }
        $overallProgress = $courseProgressCount > 0 ? $overallProgress / $courseProgressCount : 0;

        $assessment = $this->performanceAssessment->assess(
            $studentId,
            $classIds,
            $overallProgress,
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

    /**
     * Show incomplete assignments and quizzes
     */
    public function incomplete(): View
    {
        $studentId = auth()->id();

        // Get student's enrollments
        $enrollments = Enrollment::where('student_id', $studentId)
            ->where('status', 'active')
            ->with(['class.course', 'class.instructor'])
            ->get();

        $classIds = $enrollments->pluck('class_id');
        $courseIds = $enrollments->pluck('class.course_id')->filter();

        // Incomplete assignments (not submitted yet)
        $incompleteAssignments = $this->contentProgress->studentAssignmentsQuery($classIds, $courseIds)
            ->whereDoesntHave('submissions', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->with(['class.course', 'module.course', 'lesson.module.course'])
            ->orderBy('due_date')
            ->get();

        // Overdue assignments (considering extensions)
        $allAssignments = $this->contentProgress->studentAssignmentsQuery($classIds, $courseIds)
            ->whereDoesntHave('submissions', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->with(['class.course', 'module.course', 'lesson.module.course'])
            ->get();

        $overdueAssignments = $allAssignments->filter(function ($assignment) use ($studentId) {
            $effectiveDeadline = $assignment->getEffectiveDeadlineForStudent($studentId);
            return $effectiveDeadline && $effectiveDeadline->isPast();
        });

        // Overdue quizzes (considering extensions)
        $allQuizzes = $this->contentProgress->studentQuizzesQuery($classIds, $courseIds)
            ->whereDoesntHave('attempts', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->with(['class.course', 'module.course', 'lesson.module.course'])
            ->get();

        $overdueQuizzes = $allQuizzes->filter(function ($quiz) use ($studentId) {
            $effectiveDeadline = $quiz->getEffectiveDeadlineForStudent($studentId);
            return $effectiveDeadline && $effectiveDeadline->isPast();
        });

        // Overdue exams (considering extensions)
        $allExams = $this->contentProgress->studentExamsQuery($classIds, $courseIds)
            ->whereDoesntHave('attempts', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->with(['class.course'])
            ->get();

        $overdueExams = $allExams->filter(function ($exam) use ($studentId) {
            $effectiveDeadline = $exam->getEffectiveDeadlineForStudent($studentId);
            return $effectiveDeadline && $effectiveDeadline->isPast();
        });

        // Incomplete quizzes (not attempted yet)
        $incompleteQuizzes = $this->contentProgress->studentQuizzesQuery($classIds, $courseIds)
            ->whereDoesntHave('attempts', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->where('availability_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('availability_until')
                    ->orWhere('availability_until', '>=', now());
            })
            ->with(['class.course', 'module.course', 'lesson.module.course'])
            ->orderBy('availability_from')
            ->get();

        // Incomplete exams (not attempted yet)
        $incompleteExams = $this->contentProgress->studentExamsQuery($classIds, $courseIds)
            ->whereDoesntHave('attempts', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->with(['class.course'])
            ->orderBy('starts_at')
            ->get();

        return view('student.incomplete', compact(
            'enrollments',
            'incompleteAssignments',
            'overdueAssignments',
            'overdueQuizzes',
            'overdueExams',
            'incompleteQuizzes',
            'incompleteExams'
        ));
    }
}
