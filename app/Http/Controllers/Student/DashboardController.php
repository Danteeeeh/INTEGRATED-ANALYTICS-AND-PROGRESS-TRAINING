<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentExtension;
use App\Models\AssignmentSubmission;
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

        // One scoped class list for the whole page — the same one search
        // narrows to. An active enrolment pointing at another block's class
        // (transfers, AA drift, shared demo data) must not be counted in a
        // tile, drawn as a course card, or listed in a panel. The class ids,
        // course ids, course cards and every count below are derived from this
        // set, so a hidden class cannot leak back in one surface at a time.
        $enrolledClassIds = $activeEnrollments->pluck('class_id');
        $classIds = $this->contentProgress->sectionScopedClassIds($studentId, $enrolledClassIds);
        $courseIds = ClassModel::whereIn('id', $classIds)->pluck('course_id')->filter();

        // Kept for the analytics note: the enrolment exists, it is simply
        // outside this student's section, so nothing on this page shows it.
        $hiddenEnrollments = $activeEnrollments->whereNotIn('class_id', $classIds);
        $activeEnrollments = $activeEnrollments->whereIn('class_id', $classIds)->values();

        // Grades follow the same section scope as My Progress: a grade belongs
        // to this student only when its class is one of their own section's
        // classes. Both pages read the shared helper so they cannot drift apart.
        $gradeClassIds = $this->contentProgress->sectionScopedGradeClassIds($studentId, $classIds);

        // Calculate week from now for upcoming items
        $weekFromNow = now()->addDays(7);

        // Upcoming assignments (due within the next 7 days, not yet submitted)
        $upcomingAssignments = $this->contentProgress->sectionScopedAssignmentsQuery($classIds, $courseIds)
            ->whereDoesntHave('submissions', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->where('due_date', '>', now())
            ->where('due_date', '<=', $weekFromNow)
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Overdue assignments (considering extensions)
        $allAssignments = $this->contentProgress->sectionScopedAssignmentsQuery($classIds, $courseIds)
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
        $allQuizzes = $this->contentProgress->sectionScopedQuizzesQuery($classIds, $courseIds)
            ->whereDoesntHave('attempts', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->with(['class.course', 'module.course', 'lesson.module.course'])
            ->get();

        $overdueQuizzes = $allQuizzes->filter(function ($quiz) use ($studentId) {
            $effectiveDeadline = $quiz->getEffectiveDeadlineForStudent($studentId);
            return $effectiveDeadline && $effectiveDeadline->isPast();
        })->take(5);

        // Overdue exams (considering extensions). Section-scoped like the
        // Incomplete Work page: the course-wide query also matches on
        // exams.course_id, which lets a same-course class from another section
        // back onto the dashboard.
        $allExams = $this->contentProgress->sectionScopedExamsQuery($classIds, $courseIds)
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
        $upcomingQuizzes = $this->contentProgress->sectionScopedQuizzesQuery($classIds, $courseIds)
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

        // Recent quiz attempts — the same scoped quiz set the tiles count, so
        // an attempt logged against another block's class does not list here.
        $recentQuizAttempts = QuizAttempt::where('student_id', $studentId)
            ->whereIn('quiz_id', $this->contentProgress->sectionScopedQuizzesQuery($classIds, $courseIds)->pluck('quizzes.id'))
            ->with(['quiz.class', 'quiz.module.course', 'quiz.lesson.module.course'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Upcoming exams (available now, starting soon, or ending within the next 7 days)
        $upcomingExams = $this->contentProgress->sectionScopedExamsQuery($classIds, $courseIds)
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

        // Recent exam attempts — scoped like the exam tile, so a hidden
        // class's exam cannot appear in the panel either.
        $recentExamAttempts = ExamAttempt::where('student_id', $studentId)
            ->whereIn('exam_id', $this->contentProgress->sectionScopedExamsQuery($classIds, $courseIds)->pluck('exams.id'))
            ->with(['exam.class'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Upcoming virtual classes (within the next 7 days)
        $upcomingVirtualClasses = VirtualClass::whereIn('class_id', $classIds)
            // Keep the calendar window explicit before the same-day time
            // branch below. That makes the dashboard follow the same bounded
            // date query as the student virtual-class surfaces and prevents a
            // future OR branch from admitting an old meeting accidentally.
            ->whereDate('meeting_date', '>=', today())
            ->whereDate('meeting_date', '<=', $weekFromNow)
            ->where(function ($query) {
                // Future dates in the seven-day window are all upcoming.
                $query->whereDate('meeting_date', '>', today());
                // Today's meeting must still be ahead of its start time.
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

        // Recent grades — only for classes in the student's own section
        $recentGrades = Grade::where('student_id', $studentId)
            ->with('item.class.course')
            ->whereHas('item', fn ($q) => $q->where('is_released', true)
                ->whereIn('class_id', $gradeClassIds))
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
        $coursesWithContent = 0;

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
                    'has_content' => $liveProgress['has_content'] ?? true,
                    'breakdown' => $liveProgress,
                    'last_accessed' => $persisted?->updated_at,
                ];

                // A course with nothing published yet is not "0% done"; it is
                // not part of the average at all.
                if ($liveProgress['has_content'] ?? true) {
                    $overallProgress += $liveProgress['overall'];
                    $coursesWithContent++;
                }
            }
        }

        $overallProgress = $coursesWithContent > 0 ? $overallProgress / $coursesWithContent : 0;

        // Continue learning (most recent course with progress by last accessed)
        // — only courses that have something to continue.
        $continueLearning = collect($courseProgressData)
            ->filter(fn ($item) => $item['has_content'] ?? true)
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

        // Completed activities — lessons finished, assignments handed in and
        // quizzes answered. The card used to print the completed-course count,
        // so it read "0" for a student sitting on 25 released grades.
        //
        // Lessons follow the same section scope as the course cards: a lesson
        // in another section's module (or in an unpublished module) is not
        // this student's activity, so it must not be counted here either.
        $assignedModuleIds = $this->contentProgress->assignedModuleIdsForClasses($classIds);

        $completedActivities = LessonProgress::where('student_id', $studentId)
            ->where('status', LessonProgress::STATUS_COMPLETED)
            ->whereHas('lesson', fn ($q) => $q->published()->whereIn('module_id', $assignedModuleIds))
            ->count()
            + AssignmentSubmission::where('student_id', $studentId)
                ->whereIn('assignment_id', $this->contentProgress->sectionScopedAssignmentsQuery($classIds, $courseIds)->pluck('assignments.id'))
                ->whereIn('status', [
                    AssignmentSubmission::STATUS_SUBMITTED,
                    AssignmentSubmission::STATUS_GRADED,
                    AssignmentSubmission::STATUS_RETURNED,
                    AssignmentSubmission::STATUS_RESUBMITTED,
                ])
                ->distinct('assignment_id')
                ->count('assignment_id')
            + QuizAttempt::where('student_id', $studentId)
                ->whereIn('quiz_id', $this->contentProgress->sectionScopedQuizzesQuery($classIds, $courseIds)->pluck('quizzes.id'))
                ->whereIn('status', [
                    QuizAttempt::STATUS_SUBMITTED,
                    QuizAttempt::STATUS_AUTO_SUBMITTED,
                    QuizAttempt::STATUS_GRADED,
                ])
                ->count();

        // Official final grades live in AA. Until one is recorded the card would
        // read 0.0% even though graded work is already visible in My Grades, so
        // it falls back to the released LMS scores and says so in the footer.
        $finalGrades = $completedEnrollments->whereNotNull('final_grade');
        $releasedScores = Grade::where('student_id', $studentId)
            ->whereHas('item', fn ($q) => $q->where('is_released', true)->whereIn('class_id', $gradeClassIds))
            ->whereNotNull('score_percent')
            ->pluck('score_percent');
        $lmsAverageGrade = $releasedScores->isNotEmpty() ? (float) $releasedScores->avg() : 0.0;

        $stats = [
            // Course Overview
            'my_courses' => $activeEnrollments->count(),
            'total_enrollments' => $enrollments->count(),
            'active_enrollments' => $activeEnrollments->count(),
            'completed_enrollments' => $completedEnrollments->count(),

            // Academic Progress
            'average_grade' => $finalGrades->isNotEmpty() ? (float) $finalGrades->avg('final_grade') : $lmsAverageGrade,
            'average_grade_is_final' => $finalGrades->isNotEmpty(),
            'highest_grade' => $finalGrades->max('final_grade') ?? 0,
            'lowest_grade' => $finalGrades->min('final_grade') ?? 0,
            'completed_activities' => $completedActivities,
            'gpa' => $gpa,

            // Course Progress
            'course_progress' => $courseProgressData,
            'overall_progress' => round($overallProgress, 2),
            'continue_learning' => $continueLearning,

            // Assignments
            'upcoming_assignments' => $upcomingAssignments,
            'overdue_assignments' => $overdueAssignments,
            'total_assignments' => $this->contentProgress->sectionScopedAssignmentsQuery($classIds, $courseIds)->count(),

            // Quizzes
            'upcoming_quizzes' => $upcomingQuizzes,
            'overdue_quizzes' => $overdueQuizzes,
            'recent_quiz_attempts' => $recentQuizAttempts,
            'total_quizzes' => $this->contentProgress->sectionScopedQuizzesQuery($classIds, $courseIds)->count(),

            // Exams
            'upcoming_exams' => $upcomingExams,
            'overdue_exams' => $overdueExams,
            'recent_exam_attempts' => $recentExamAttempts,
            'total_exams' => $this->contentProgress->sectionScopedExamsQuery($classIds, $courseIds)->count(),

            // Virtual Classes
            'upcoming_virtual_classes' => $upcomingVirtualClasses,
            'total_virtual_classes' => VirtualClass::whereIn('class_id', $classIds)
                ->where('status', 'published')
                ->whereDate('meeting_date', '>=', today())
                ->count(),

            // Grades and Feedback
            'recent_grades' => $recentGrades,
            'recent_feedback' => $recentFeedback,
            // The card calls this "grades released", so released is what it counts.
            'total_grades' => Grade::where('student_id', $studentId)
                ->whereHas('item', fn ($q) => $q->whereIn('class_id', $gradeClassIds)->where('is_released', true))
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
            // The analytics class filter only offers classes inside the
            // student's section. A class outside that scope can only ever
            // return the "not part of your section" notice, so the notice is
            // left as a fallback for a stale or hand-made request instead of
            // being something the dropdown itself can produce.
            'analytics_enrollments' => $activeEnrollments->whereIn('class_id', $gradeClassIds),
            // The classes the section scope kept out, by course code. Nothing
            // on this page shows them any more — not the cards, not the counts
            // — so this note is the only place that names them.
            'analytics_hidden_classes' => $hiddenEnrollments
                ->filter(fn ($enrollment) => $enrollment->class !== null)
                ->map(fn ($enrollment) => $enrollment->class->course?->code ?? $enrollment->class->code)
                ->filter()
                ->values(),

            // Academic Period Info
            'current_period' => $activeEnrollments->first()?->class?->academicPeriod,
        ];

        return view('student.dashboard', compact('stats'));
    }

    protected function calculateLearningStreak(int $studentId): int
    {
        // One definition for every student surface. Counting lesson updates
        // here but completed lessons on My Progress produced two different
        // "day streak" values for the same student.
        return $this->contentProgress->learningStreak($studentId);
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
                'type' => 'required|in:all,courses,modules,lessons,assignments,quizzes,exams,grades',
            ]);

            $studentId = auth()->id();
            $query = $validated['query'];
            $type = $validated['type'];
            $results = [];
            $types = $type === 'all' ? ['courses', 'modules', 'lessons', 'assignments', 'quizzes', 'exams', 'grades'] : [$type];

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
        $enrolledClassIds = Enrollment::where('student_id', $studentId)
            ->where('status', 'active')
            ->pluck('class_id');

        // Results honour the same section scope as the tiles and charts: an
        // active enrolment in another block's class (transfers, AA drift) must
        // not put that block's courses, grades, quizzes, exams or lessons into
        // this student's search results. Narrowing the classes up front is what
        // keeps the class-level rows honest too — the courses those classes
        // point at, and the modules their sections were assigned.
        $classIds = $this->contentProgress->sectionScopedClassIds($studentId, $enrolledClassIds);
        $courseIds = ClassModel::whereIn('id', $classIds)->pluck('course_id')->filter();

        // Lessons and modules follow the section's assigned modules, the same
        // scope the Completed Activities card and the plan pages count.
        $moduleIds = $this->contentProgress->assignedModuleIdsForClasses($classIds);

        switch ($type) {
            case 'courses':
                // Only the courses behind the student's own section's classes;
                // a stale enrolment in another block must not be findable by
                // the course code it happens to share.
                return Enrollment::where('student_id', $studentId)
                    ->whereIn('class_id', $classIds)
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
                // The section-scoped query, not the wider browse one: work that
                // hangs off another section's module is not this student's
                // assignment even when it sits in their course.
                return $this->contentProgress->sectionScopedAssignmentsQuery($classIds, $courseIds)
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
                return $this->contentProgress->sectionScopedQuizzesQuery($classIds, $courseIds)
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

            case 'exams':
                // The dropdown has always offered "Exams" and the panel already
                // knows how to render an exam result (it prints "Starts <date>"),
                // but 'exams' was missing from the type allow-list, so the
                // request was rejected before it could reach a case.
                return $this->contentProgress->sectionScopedExamsQuery($classIds, $courseIds)
                    ->where('exams.title', 'like', "%{$query}%")
                    ->with(['class.course', 'module.course'])
                    ->limit(10)
                    ->get()
                    ->map(fn ($e) => [
                        'id' => $e->id,
                        'title' => $e->title,
                        'entity' => 'exam',
                        'class_code' => $e->class?->code ?? '',
                        'course_title' => ($e->class?->course ?? $e->module?->course)?->title ?? '',
                        'starts_at' => $e->starts_at?->format('Y-m-d'),
                        'status' => $e->status,
                    ])
                    ->toArray();

            case 'modules':
                // Module search follows the section's assigned modules, not
                // every module of the course: a course shared by two blocks
                // gives each one a different set.
                return Module::whereIn('id', $moduleIds)
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
                // Lessons follow the section's assigned modules, the same scope
                // the Completed Activities card and the plan pages count.
                return Lesson::whereIn('module_id', $moduleIds)
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
                // $classIds comes from sectionScopedClassIds(), the helper the
                // Average Grade tile reads through sectionScopedGradeClassIds().
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
            if (! in_array($type, ['grades', 'quizzes', 'exams', 'progress'], true)) {
                $type = 'grades';
            }

            $studentId = auth()->id();

            // Classes this student may look at. Anything outside this set is not
            // theirs to view, so a forged class_id is rejected outright rather
            // than silently widened to "all classes".
            $enrolledClassIds = Enrollment::where('student_id', $studentId)
                ->where('status', 'active')
                ->pluck('class_id');

            $requestedClassId = $request->input('class_id');

            if ($requestedClassId !== null && $requestedClassId !== '') {
                $request->validate([
                    'class_id' => ['integer', 'in:'.$enrolledClassIds->implode(',')],
                ], [
                    'class_id.in' => 'You are not enrolled in that class.',
                ]);

                $classIds = collect([(int) $requestedClassId]);
            } else {
                $classIds = $enrolledClassIds;
            }

            // The Grade Trend shares the section scope of the Average Grade
            // tile. A stale AA enrolment in another block's class is not a
            // reason for the chart under that tile to draw a point the tile
            // has already decided does not count.
            $gradeClassIds = $this->contentProgress->sectionScopedGradeClassIds($studentId, $classIds);

            // Courses behind the classes in view, for the section-scoped quiz
            // query below. Derived from $classIds rather than every enrolment,
            // otherwise picking one class would still pull its whole course in.
            $courseIds = ClassModel::whereIn('id', $classIds)->pluck('course_id')->filter();

            // The dropdown offers every active enrolment, stale rows included.
            // When one of those is picked the section-scoped queries above
            // return nothing, and an empty chart with no reason reads as a
            // broken panel — so the answer carries the reason with it.
            $classOutOfScope = $requestedClassId !== null
                && $requestedClassId !== ''
                && ! $gradeClassIds->contains((int) $requestedClassId);

            switch ($type) {
                case 'grades':
                    $grades = Grade::where('student_id', $studentId)
                        ->with('item.class.course')
                        ->whereHas('item', fn ($q) => $q->where('is_released', true)
                            ->whereIn('class_id', $gradeClassIds))
                        ->orderBy('graded_at')
                        ->get();

                    // When more than one class is in view, name the course on
                    // every point. "Final Exam" alone is ambiguous across
                    // courses, and an unqualified average is meaningless.
                    $scopedToOneClass = $classIds->count() === 1;

                    $points = $grades->map(function ($g) use ($scopedToOneClass) {
                        $title = $g->item?->title ?? 'Grade';
                        $course = $g->item?->class?->course?->title;

                        return [
                            'name' => ($scopedToOneClass || ! $course)
                                ? $title
                                : $title.' · '.$course,
                            'score' => round($g->score_percent ?? 0, 1),
                            'date' => $g->graded_at?->format('M j'),
                            'course' => $course,
                        ];
                    });

                    $analytics = [
                        'labels' => $points->pluck('name')->toArray(),
                        'scores' => $points->pluck('score')->toArray(),
                        'dates' => $points->pluck('date')->toArray(),
                        'average' => round($points->avg('score') ?? 0, 1),
                        'total_grades' => $points->count(),
                        'scope' => $scopedToOneClass
                            ? ($grades->first()->item?->class?->course?->title ?? 'Selected class')
                            : 'All enrolled classes',
                    ];
                    break;

                case 'quizzes':
                    // Quiz Performance reads the same section-scoped quiz set as
                    // the Quiz Progress tile: class-level quizzes for the
                    // student's classes plus module work inside the modules
                    // their section was assigned. Filtering on class_id alone
                    // let another section's module quiz onto this chart.
                    $scopedQuizIds = $this->contentProgress
                        ->sectionScopedQuizzesQuery($classIds, $courseIds)
                        ->pluck('quizzes.id');

                    $attempts = QuizAttempt::where('student_id', $studentId)
                        ->whereIn('status', [
                            QuizAttempt::STATUS_SUBMITTED,
                            QuizAttempt::STATUS_AUTO_SUBMITTED,
                            QuizAttempt::STATUS_GRADED,
                        ])
                        ->whereIn('quiz_id', $scopedQuizIds)
                        ->with('quiz.class.course')
                        ->orderBy('created_at')
                        ->get()
                        ->map(function ($a) use ($classIds) {
                            $title = $a->quiz?->title ?? 'Quiz';
                            $course = $a->quiz?->class?->course?->title;

                            return [
                                'quiz' => ($classIds->count() === 1 || ! $course)
                                    ? $title
                                    : $title.' · '.$course,
                                // `score` is raw points (e.g. 3 of 10), not a
                                // percentage. Falling back to it drew a 3% bar
                                // for a 30% result whenever score_percent was
                                // missing.
                                'score' => round($a->score_percent ?? 0, 1),
                            ];
                        });

                    $analytics = [
                        'labels' => $attempts->pluck('quiz')->map(fn ($t) => Str::limit($t, 32))->toArray(),
                        'scores' => $attempts->pluck('score')->toArray(),
                        'total_attempts' => $attempts->count(),
                        'best_score' => round(max($attempts->pluck('score')->toArray() ?: [0]), 1),
                        'average' => round($attempts->avg('score') ?? 0, 1),
                        'scope' => $classIds->count() === 1 ? 'Selected class' : 'All enrolled classes',
                    ];
                    break;

                case 'exams':
                    // The dropdown has always offered "Exam Performance", but
                    // 'exams' was missing from the allow-list above, so it fell
                    // through to the grades branch and showed the wrong chart.
                    $examAttempts = ExamAttempt::where('student_id', $studentId)
                        ->whereIn('status', [
                            ExamAttempt::STATUS_SUBMITTED,
                            ExamAttempt::STATUS_AUTO_SUBMITTED,
                            ExamAttempt::STATUS_GRADED,
                            ExamAttempt::STATUS_REVIEWED,
                        ])
                        ->whereHas('exam', fn ($q) => $q->whereIn('class_id', $classIds))
                        ->with('exam.class.course')
                        ->orderBy('submitted_at')
                        ->get()
                        ->map(function ($a) use ($classIds) {
                            $title = $a->exam?->title ?? 'Exam';
                            $course = $a->exam?->class?->course?->title;

                            return [
                                'exam' => ($classIds->count() === 1 || ! $course)
                                    ? $title
                                    : $title.' · '.$course,
                                'score' => round($a->score_percent ?? 0, 1),
                                'date' => ($a->submitted_at ?? $a->started_at)?->format('M j'),
                            ];
                        });

                    $analytics = [
                        'labels' => $examAttempts->pluck('exam')->map(fn ($t) => Str::limit($t, 32))->toArray(),
                        'scores' => $examAttempts->pluck('score')->toArray(),
                        'dates' => $examAttempts->pluck('date')->toArray(),
                        'total_attempts' => $examAttempts->count(),
                        'average' => round($examAttempts->avg('score') ?? 0, 1),
                        'best_score' => round(max($examAttempts->pluck('score')->toArray() ?: [0]), 1),
                        'scope' => $classIds->count() === 1 ? 'Selected class' : 'All enrolled classes',
                    ];
                    break;

                case 'progress':
                    // Honour the class filter here too, otherwise selecting a
                    // class still returns every course's progress.
                    $enrollments = Enrollment::where('student_id', $studentId)
                        ->where('status', 'active')
                        ->whereIn('class_id', $classIds)
                        ->with('class.course')
                        ->get();

                    $progressData = [];
                    foreach ($enrollments as $enrollment) {
                        $course = $enrollment->class->course;
                        if (! $course) {
                            continue;
                        }
                        $live = $this->contentProgress->calculateCourseLiveProgress($course, $studentId, $enrollment->class_id);

                        // Skip courses that have nothing published yet — a 0%
                        // bar there reads as a failing student, not empty course.
                        if (! ($live['has_content'] ?? true)) {
                            continue;
                        }

                        $progressData[] = [
                            'code' => $course->code,
                            'course' => $course->title ?? 'Course',
                            'progress' => round($live['overall'] ?? 0, 1),
                        ];
                    }

                    $analytics = [
                        // Course codes first: with only the title, courses with
                        // long or similar names collapsed into the same bar
                        // label. The code is short enough to survive the cap.
                        'labels' => collect($progressData)->map(fn ($row) => trim(
                            ($row['code'] ? $row['code'].' · ' : '').Str::limit($row['course'], 28)
                        ))->toArray(),
                        'progress' => collect($progressData)->pluck('progress')->toArray(),
                        'overall' => round(collect($progressData)->avg('progress') ?? 0, 1),
                        'total_courses' => count($progressData),
                    ];
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'notice' => $classOutOfScope
                    ? 'This class is not part of your section, so its grades and results are not counted here.'
                    : null,
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

        // Same section scope as the rendered dashboard: the auto-refresh must
        // not quietly put a hidden class back into the counts it patches in.
        $activeEnrollments = $enrollments->where('status', 'active');
        $classIds = $this->contentProgress->sectionScopedClassIds(
            $studentId,
            $activeEnrollments->pluck('class_id')
        );
        $courseIds = ClassModel::whereIn('id', $classIds)->pluck('course_id')->filter();
        $activeEnrollments = $activeEnrollments->whereIn('class_id', $classIds)->values();

        $upcomingAssignments = $this->contentProgress->sectionScopedAssignmentsQuery($classIds, $courseIds)
            ->where('due_date', '>', now())
            ->orderBy('due_date')->limit(5)->get();

        $upcomingQuizzes = $this->contentProgress->sectionScopedQuizzesQuery($classIds, $courseIds)
            ->where('availability_from', '>', now())
            ->orderBy('availability_from')->limit(5)->get();

        // The section-scoped query, matching the rendered dashboard: work in
        // another section's module of the same course is not overdue *for this
        // student*, so the auto-refresh must not report it either.
        $overdueAssignments = $this->contentProgress->sectionScopedAssignmentsQuery($classIds, $courseIds)
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
                // Same rule as the dashboard: empty courses do not count.
                if ($liveProgress['has_content'] ?? true) {
                    $overallProgress += $liveProgress['overall'];
                    $courseProgressCount++;
                }
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

        // Same section scope as the dashboard and search: a stale enrolment in
        // another block's class must not put that block's pending work on this
        // page. Narrowing the class list also narrows every query below.
        $classIds = $this->contentProgress->sectionScopedClassIds(
            $studentId,
            $enrollments->pluck('class_id')
        );
        $courseIds = ClassModel::whereIn('id', $classIds)->pluck('course_id')->filter();
        $enrollments = $enrollments->whereIn('class_id', $classIds)->values();

        // Incomplete assignments (not submitted yet)
        $incompleteAssignments = $this->contentProgress->sectionScopedAssignmentsQuery($classIds, $courseIds)
            ->whereDoesntHave('submissions', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->with(['class.course', 'module.course', 'lesson.module.course'])
            ->orderBy('due_date')
            ->get();

        // Overdue assignments (considering extensions)
        $allAssignments = $this->contentProgress->sectionScopedAssignmentsQuery($classIds, $courseIds)
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
        $allQuizzes = $this->contentProgress->sectionScopedQuizzesQuery($classIds, $courseIds)
            ->whereDoesntHave('attempts', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->with(['class.course', 'module.course', 'lesson.module.course'])
            ->get();

        $overdueQuizzes = $allQuizzes->filter(function ($quiz) use ($studentId) {
            $effectiveDeadline = $quiz->getEffectiveDeadlineForStudent($studentId);
            return $effectiveDeadline && $effectiveDeadline->isPast();
        });

        // Overdue exams (considering extensions). The section-scoped query, not
        // the course-wide one: an exam in another section's class of the same
        // course is not this student's exam.
        $allExams = $this->contentProgress->sectionScopedExamsQuery($classIds, $courseIds)
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
        $incompleteQuizzes = $this->contentProgress->sectionScopedQuizzesQuery($classIds, $courseIds)
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
        $incompleteExams = $this->contentProgress->sectionScopedExamsQuery($classIds, $courseIds)
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
