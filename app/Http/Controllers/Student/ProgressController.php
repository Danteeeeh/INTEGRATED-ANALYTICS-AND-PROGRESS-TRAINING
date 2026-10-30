<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\CourseCompletion;
use App\Models\CourseProgress;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\LessonProgress;
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
        $contentProgress = app(\App\Services\ContentProgressService::class);
        $courseProgressData = [];
        foreach ($activeEnrollments as $enrollment) {
            $course = $enrollment->class->course;
            if ($course) {
                // Use live calculation so progress always reflects the real DB state,
                // instead of relying on a potentially stale CourseProgress row.
                $liveProgress = $contentProgress->calculateCourseLiveProgress($course, $studentId, $enrollment->class_id);

                $progress = CourseProgress::where('student_id', $studentId)
                    ->where('class_id', $enrollment->class_id)
                    ->first();

                // Take the counts from the same calculation as the percentage:
                // they are already scoped to the modules assigned to this
                // student's section, so the card can never disagree with the
                // Modules page.
                $courseProgressData[] = [
                    'course' => $course,
                    'class' => $enrollment->class,
                    'progress' => $liveProgress['overall'],
                    'has_content' => $liveProgress['has_content'] ?? true,
                    'last_accessed' => $progress ? $progress->updated_at : null,
                    'modules_completed' => $liveProgress['completed']['modules'],
                    'total_modules' => $liveProgress['totals']['modules'],
                    'lessons_completed' => $liveProgress['completed']['lessons'],
                    'total_lessons' => $liveProgress['totals']['lessons'],
                ];
            }
        }

        // Overall statistics — only courses that actually have content count.
        // A course with nothing published yet would otherwise pull a perfect
        // student's average down with a phantom 0%.
        $totalCourses = $activeEnrollments->count();
        $coursesWithContent = collect($courseProgressData)->where('has_content', true);
        $overallProgress = $coursesWithContent->isNotEmpty()
            ? $coursesWithContent->avg('progress')
            : 0;

        // Assignment progress — the same scope the course progress bars use:
        // published work for the student's classes, and module/lesson work only
        // inside the modules their section was assigned. Another section's
        // module work would otherwise make this tile disagree with the cards.
        $classIds = $activeEnrollments->pluck('class_id');
        $courseIds = $activeEnrollments->pluck('class.course_id')->filter();

        $assignmentsBase = $contentProgress->sectionScopedAssignmentsQuery($classIds, $courseIds);
        $totalAssignments = (clone $assignmentsBase)->count();
        $submittedAssignments = $totalAssignments > 0
            ? AssignmentSubmission::where('student_id', $studentId)
                ->whereIn('assignment_id', (clone $assignmentsBase)->pluck('assignments.id'))
                ->whereIn('status', [
                    AssignmentSubmission::STATUS_SUBMITTED,
                    AssignmentSubmission::STATUS_GRADED,
                    AssignmentSubmission::STATUS_RETURNED,
                    AssignmentSubmission::STATUS_RESUBMITTED,
                ])
                // A resubmission is another row for the same assignment; count
                // the assignment once so this can never pass the total.
                ->distinct('assignment_id')
                ->count('assignment_id')
            : 0;
        $assignmentProgress = $totalAssignments > 0
            ? ($submittedAssignments / $totalAssignments) * 100
            : 0;

        // Quiz progress — a quiz counts once, however many attempts it took.
        // Counting raw attempts (including attempts still in progress) pushed
        // this past 100% and printed a negative "remaining" count.
        $quizzesBase = $contentProgress->sectionScopedQuizzesQuery($classIds, $courseIds);
        $totalQuizzes = (clone $quizzesBase)->count();
        $quizIds = (clone $quizzesBase)->pluck('quizzes.id');
        $completedAttemptStatuses = [
            QuizAttempt::STATUS_SUBMITTED,
            QuizAttempt::STATUS_AUTO_SUBMITTED,
            QuizAttempt::STATUS_GRADED,
        ];
        $quizzesCompleted = QuizAttempt::where('student_id', $studentId)
            ->whereIn('quiz_id', $quizIds)
            ->whereIn('status', $completedAttemptStatuses)
            ->distinct()
            ->pluck('quiz_id')
            ->count();
        $quizAttempts = QuizAttempt::where('student_id', $studentId)
            ->whereIn('quiz_id', $quizIds)
            ->whereIn('status', $completedAttemptStatuses)
            ->count();
        $quizProgress = $totalQuizzes > 0
            ? min(100, ($quizzesCompleted / $totalQuizzes) * 100)
            : 0;

        // Grades progress — released LMS scores from the classes of the
        // student's own section, so another block's grades can never pull this
        // tile away from the courses shown above it. The scope is shared with
        // the dashboard card.
        $gradeClassIds = $contentProgress->sectionScopedGradeClassIds($studentId, $classIds);

        // The average used to come from the ten newest rows that also feed the
        // list below, so the headline number moved with the list limit. Both
        // now read the same section-scoped set.
        $releasedGrades = Grade::where('student_id', $studentId)
            ->whereHas('item', fn ($q) => $q->where('is_released', true)->whereIn('class_id', $gradeClassIds))
            ->whereNotNull('score_percent')
            ->pluck('score_percent');
        $averageGrade = $releasedGrades->isNotEmpty() ? (float) $releasedGrades->avg() : 0;
        $gradedItemCount = $releasedGrades->count();

        $recentGrades = Grade::where('student_id', $studentId)
            ->with('item.class.course')
            ->whereHas('item', fn ($q) => $q->where('is_released', true)->whereIn('class_id', $gradeClassIds))
            ->orderBy('graded_at', 'desc')
            ->limit(10)
            ->get();

        // Learning streak
        $learningStreak = $this->calculateLearningStreak($studentId);

        // Course completions — a completion belongs to a class, and only the
        // classes the student is actually enrolled in (their own section) may
        // count. An imported or transferred row from another section's class
        // used to pad the timeline with a course the student never sat here.
        // Dropped enrolments are not history worth showing.
        $completionClassIds = $enrollments
            ->where('status', '!=', Enrollment::STATUS_DROPPED)
            ->pluck('class_id')
            ->unique()
            ->values();

        $completions = CourseCompletion::where('student_id', $studentId)
            ->whereIn('class_id', $completionClassIds)
            ->with('class.course')
            ->orderBy('completed_at', 'desc')
            ->get();

        // Weekly activity — same scope as the tiles above.
        $weeklyActivity = $this->getWeeklyActivity($studentId, $classIds, $courseIds);

        // Completion timeline — same section scope as the list above.
        $completionTimeline = $this->getCompletionTimeline($studentId, $completionClassIds);

        return view('student.progress', compact(
            'courseProgressData',
            'overallProgress',
            'totalCourses',
            'assignmentProgress',
            'submittedAssignments',
            'totalAssignments',
            'quizProgress',
            'quizAttempts',
            'quizzesCompleted',
            'totalQuizzes',
            'recentGrades',
            'averageGrade',
            'gradedItemCount',
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
        // Shared with the dashboard so both pages print the same streak.
        return app(\App\Services\ContentProgressService::class)->learningStreak($studentId);
    }

    /**
     * What the student actually did on each of the last seven days, limited to
     * the work their own section can open.
     *
     * It used to count every lesson and submission row the student owned and
     * every quiz attempt in any state, so opening a quiz (or working through
     * another section's module) drew a tall bar of activity that never
     * happened. Lessons follow the assigned-module scope of the course cards;
     * quizzes and assignments use the section-scoped queries; only finished
     * work counts.
     */
    protected function getWeeklyActivity(int $studentId, $classIds, $courseIds): array
    {
        $contentProgress = app(\App\Services\ContentProgressService::class);

        $moduleIds = $contentProgress->assignedModuleIdsForClasses($classIds);
        $assignmentIds = $contentProgress->sectionScopedAssignmentsQuery($classIds, $courseIds)
            ->pluck('assignments.id');
        $quizIds = $contentProgress->sectionScopedQuizzesQuery($classIds, $courseIds)
            ->pluck('quizzes.id');

        $activity = [];
        $maxLessons = 0;
        $maxQuizzes = 0;
        $maxAssignments = 0;

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $lessonsCompleted = LessonProgress::where('student_id', $studentId)
                ->whereDate('completed_at', $date)
                ->where('status', LessonProgress::STATUS_COMPLETED)
                ->whereHas('lesson', fn ($q) => $q->published()->whereIn('module_id', $moduleIds))
                ->count();
            $quizzesTaken = QuizAttempt::where('student_id', $studentId)
                ->whereIn('quiz_id', $quizIds)
                ->whereIn('status', [
                    QuizAttempt::STATUS_SUBMITTED,
                    QuizAttempt::STATUS_AUTO_SUBMITTED,
                    QuizAttempt::STATUS_GRADED,
                ])
                ->whereDate('created_at', $date)
                ->count();
            $assignmentsSubmitted = AssignmentSubmission::where('student_id', $studentId)
                ->whereIn('assignment_id', $assignmentIds)
                ->whereIn('status', [
                    AssignmentSubmission::STATUS_SUBMITTED,
                    AssignmentSubmission::STATUS_GRADED,
                    AssignmentSubmission::STATUS_RETURNED,
                    AssignmentSubmission::STATUS_RESUBMITTED,
                ])
                ->whereDate('submitted_at', $date)
                // A resubmission is another row; the assignment was handed in
                // once, so one bar rather than two.
                ->distinct('assignment_id')
                ->count('assignment_id');

            $maxLessons = max($maxLessons, $lessonsCompleted);
            $maxQuizzes = max($maxQuizzes, $quizzesTaken);
            $maxAssignments = max($maxAssignments, $assignmentsSubmitted);

            $activity[] = [
                'date' => $date->format('D'),
                'lessons_completed' => $lessonsCompleted,
                'quizzes_taken' => $quizzesTaken,
                'assignments_submitted' => $assignmentsSubmitted,
            ];
        }

        return [
            'data' => $activity,
            'max' => [
                'lessons' => max($maxLessons, 1),
                'quizzes' => max($maxQuizzes, 1),
                'assignments' => max($maxAssignments, 1),
            ],
        ];
    }

    protected function getCompletionTimeline(int $studentId, $classIds): array
    {
        return CourseCompletion::where('student_id', $studentId)
            ->whereIn('class_id', $classIds)
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
