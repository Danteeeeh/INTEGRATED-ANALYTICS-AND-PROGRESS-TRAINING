<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\AssessmentAutoSubmitService;
use App\Services\ContentProgressService;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ExamController extends Controller
{
    /**
     * How long after the duration ends a submission may still arrive and have
     * its posted answers counted. The page submits itself ~2 seconds after
     * the clock hits zero; this covers that delay, latency and clock skew
     * without accepting an hours-late submission.
     */
    private const TIMEOUT_GRACE_SECONDS = 120;

    public function __construct(
        protected ExamService $examService,
        protected ContentProgressService $contentProgress,
        protected AssessmentAutoSubmitService $assessmentAutoSubmit,
    ) {}

    public function index(Course $course): View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            abort(403);
        }

        // Exams through the shared section scope. The old query matched on
        // `exams.course_id`, which let a class-level exam from another block's
        // class of the same course into this list.
        $classIds = $this->contentProgress->sectionScopedClassIdsForCourse($studentId, $course->id);

        $exams = $this->contentProgress
            ->sectionScopedExamsQuery($classIds, collect([$course->id]))
            ->with([
                'attempts' => function ($q) use ($studentId) {
                    $q->ofStudent($studentId)->latest();
                },
                // The card states how many questions an exam holds, so a
                // student can tell a finished paper from one still being built.
                'questions',
            ])
            ->withCount('questions')
            ->withSum('questions', 'exam_questions.points')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('student.exams.index', compact('course', 'exams', 'enrollment'));
    }

    public function show(Course $course, Exam $exam): View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            abort(403);
        }

        $exam->load('questions.choices');

        $myAttempts = ExamAttempt::ofExam($exam->id)
            ->ofStudent($studentId)
            ->orderBy('attempt_number', 'desc')
            ->paginate(5);

        $inProgressAttempt = ExamAttempt::ofExam($exam->id)
            ->ofStudent($studentId)
            ->inProgress()
            ->first();

        $isOverdue = $exam->isOverdueForStudent($studentId);
        $effectiveDeadline = $exam->getEffectiveDeadlineForStudent($studentId);

        return view('student.exams.show', compact('course', 'exam', 'enrollment', 'myAttempts', 'inProgressAttempt', 'isOverdue', 'effectiveDeadline'));
    }

    public function confirmStart(Course $course, Exam $exam): View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            abort(403);
        }

        if (!$exam->isAvailable()) {
            return redirect()->route('student.courses.exams.show', [$course, $exam])
                ->with('error', 'This exam is not currently available.');
        }

        if ($exam->isOverdueForStudent($studentId)) {
            return redirect()->route('student.courses.exams.show', [$course, $exam])
                ->with('error', 'This exam is overdue and no longer available for attempts.');
        }

        return view('student.exams.confirm', compact('course', 'exam', 'enrollment'));
    }

    /**
 * An exam with nothing on it cannot be sat.
 *
 * Without this a student could start an attempt on an exam the instructor had
 * not finished building, be handed a blank paper, and "complete" it — which
 * recorded an attempt and a result for work that did not exist.
 */
private function assertHasQuestions(Exam $exam): bool
    {
        if ($exam->questions()->exists()) {
            return true;
        }

        return false;
    }

    public function startAttempt(Course $course, Exam $exam): RedirectResponse|View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            abort(403);
        }

        if (!$exam->isAvailable()) {
            return redirect()->route('student.courses.exams.show', [$course, $exam])
                ->with('error', 'This exam is not currently available.');
        }

        if ($exam->isOverdueForStudent($studentId)) {
            return redirect()->route('student.courses.exams.show', [$course, $exam])
                ->with('error', 'This exam is overdue and no longer available for attempts.');
        }

        if (! $this->assertHasQuestions($exam)) {
            return redirect()->route('student.courses.exams.show', [$course, $exam])
                ->with('error', 'This exam has no questions yet. Please check back later.');
        }

        $inProgress = ExamAttempt::ofExam($exam->id)
            ->ofStudent($studentId)
            ->inProgress()
            ->first();

        if ($inProgress) {
            if ($exam->duration_minutes && $exam->auto_submit_on_timeout) {
                $deadline = $inProgress->started_at->addMinutes($exam->duration_minutes);
                if (now()->gte($deadline)) {
                    return $this->autoSubmitAttempt($inProgress, $course, $exam);
                }
            }

            return $this->attemptView($course, $exam, $enrollment, $inProgress);
        }

        $attemptCount = ExamAttempt::ofExam($exam->id)->ofStudent($studentId)->count();
        if ($exam->attempt_limit && $attemptCount >= $exam->attempt_limit) {
            return redirect()->route('student.courses.exams.show', [$course, $exam])
                ->with('error', 'You have reached the maximum number of attempts for this exam.');
        }

        return $this->attemptView($course, $exam, $enrollment, $this->examService->startAttempt($exam));
    }

    /**
     * The one place the attempt page's data is assembled, so both the resumed
     * and the freshly-started attempt get the same clock value.
     */
    private function attemptView(Course $course, Exam $exam, Enrollment $enrollment, ExamAttempt $attempt): View
    {
        return view('student.exams.attempt', [
            'course' => $course,
            'exam' => $exam,
            'enrollment' => $enrollment,
            'inProgress' => $attempt,
            'remainingSeconds' => $this->remainingSeconds($exam, $attempt),
        ]);
    }

    /**
     * Seconds left on the attempt, computed from started_at rather than from
     * the duration alone: reloading or reconnecting must resume the same
     * clock, not restart a fresh one.
     */
    private function remainingSeconds(Exam $exam, ExamAttempt $attempt): int
    {
        if (! $exam->duration_minutes || ! $attempt->started_at) {
            return 0;
        }

        $elapsed = (int) $attempt->started_at->diffInSeconds(now());

        return max(0, ((int) $exam->duration_minutes * 60) - $elapsed);
    }

    /**
     * Create the attempt and hand the student to the exam page.
     *
     * The confirm screen's "Start Exam" button is a form, so it POSTs here
     * rather than linking. Previously it POSTed to the attempt path, which is
     * the *submit* handler: that looks for an attempt already in progress,
     * finds none because nothing had started it, and firstOrFail() answered
     * 404 — so every student who used the button hit a dead end.
     *
     * Starts are idempotent (an attempt already in progress is reused) and this
     * redirects to the GET route, so a refresh cannot restart the clock.
     */
    public function beginAttempt(Course $course, Exam $exam): RedirectResponse
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            abort(403);
        }

        if (!$exam->isAvailable()) {
            return redirect()->route('student.courses.exams.show', [$course, $exam])
                ->with('error', 'This exam is not currently available.');
        }

        if ($exam->isOverdueForStudent($studentId)) {
            return redirect()->route('student.courses.exams.show', [$course, $exam])
                ->with('error', 'This exam is overdue and no longer available for attempts.');
        }

        if (! $this->assertHasQuestions($exam)) {
            return redirect()->route('student.courses.exams.show', [$course, $exam])
                ->with('error', 'This exam has no questions yet. Please check back later.');
        }

        $existing = ExamAttempt::ofExam($exam->id)
            ->ofStudent($studentId)
            ->inProgress()
            ->first();

        if (!$existing) {
            $attemptCount = ExamAttempt::ofExam($exam->id)->ofStudent($studentId)->count();

            if ($exam->attempt_limit && $attemptCount >= $exam->attempt_limit) {
                return redirect()->route('student.courses.exams.show', [$course, $exam])
                    ->with('error', 'You have reached the maximum number of attempts for this exam.');
            }

            $this->examService->startAttempt($exam);
        }

        return redirect()->route('student.courses.exams.attempt.start', [$course, $exam]);
    }

    public function storeAttempt(Request $request, Course $course, Exam $exam): RedirectResponse
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            abort(403);
        }

        $attempt = ExamAttempt::ofExam($exam->id)
            ->ofStudent($studentId)
            ->inProgress()
            ->first();

        // firstOrFail() answered 404 whenever a submit arrived without a live
        // attempt — a stale tab, a double click, a lost session. That is a dead
        // end for the student, so send them back to start instead.
        if (!$attempt) {
            return redirect()->route('student.courses.exams.confirm', [$course, $exam])
                ->with('error', 'Your attempt is no longer open. Please start it again.');
        }

        // The page posts the answers it still holds when the clock reaches
        // zero. Auto-submitting straight from the database discarded them:
        // answers live in localStorage until this request lands, so the rows
        // were empty and every timed-out exam scored 0. Accept the posted
        // answers inside a short grace window instead; only a genuinely late
        // request falls back to the stored rows.
        if ($exam->duration_minutes && $exam->auto_submit_on_timeout) {
            $deadline = $attempt->started_at->addMinutes($exam->duration_minutes);
            if (now()->gte($deadline)
                && ! now()->lessThanOrEqualTo($deadline->copy()->addSeconds(self::TIMEOUT_GRACE_SECONDS))) {
                return $this->autoSubmitAttempt($attempt, $course, $exam);
            }
        }

        $answers = $request->input('answers', []);

        $attempt = $this->examService->submitAttempt($attempt, $answers);

        return redirect()->route('student.courses.exams.attempts.show', [$course, $exam, $attempt])
            ->with('status', 'Exam submitted successfully!');
    }

    protected function autoSubmitAttempt(ExamAttempt $attempt, Course $course, Exam $exam): RedirectResponse
    {
        // The same locked finalizer the scheduler uses, so a late browser
        // request and a scheduled run cannot grade the attempt twice.
        $this->assessmentAutoSubmit->autoSubmitExamAttempt($attempt);

        return redirect()->route('student.courses.exams.attempts.show', [$course, $exam, $attempt])
            ->with('warning', 'Exam time expired. Answers were auto-submitted.');
    }

    /**
     * Record a tab-switch or copy/paste event for the current attempt.
     *
     * Events are only written when the instructor enabled that detector and
     * the attempt is still open. A closed attempt accepts the request silently
     * so a late beacon from the attempt page cannot resurrect a finished exam.
     */
    public function recordProctoringEvent(Request $request, Course $course, Exam $exam): Response
    {
        if ((int) $exam->class?->course_id !== (int) $course->id) {
            abort(404);
        }

        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        $attempt = ExamAttempt::ofExam($exam->id)
            ->ofStudent($studentId)
            ->inProgress()
            ->first();

        if (! $attempt) {
            return response()->noContent();
        }

        $validated = $request->validate([
            'event' => ['required', 'string', 'in:tab_switch,copy_paste'],
        ]);

        $event = $validated['event'];
        $enabled = $event === 'tab_switch'
            ? (bool) $exam->detect_tab_switch
            : (bool) $exam->detect_copy_paste;

        if (! $enabled) {
            return response()->noContent();
        }

        $this->examService->recordProctoringEvent($attempt, $event, [
            'source' => 'attempt_page',
        ]);

        return response()->noContent();
    }

    public function showAttempt(Course $course, Exam $exam, ExamAttempt $attempt): View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            abort(403);
        }

        if ($attempt->student_id !== $studentId) {
            abort(403);
        }

        $attempt->load('answers.question.choices');

        // Check if results should be visible
        $showResults = $this->shouldShowResults($exam, $attempt);

        return view('student.exams.attempt_show', compact('course', 'exam', 'attempt', 'enrollment', 'showResults'));
    }

    protected function shouldShowResults(Exam $exam, ExamAttempt $attempt): bool
    {
        return match($exam->result_visibility) {
            'immediately' => true,
            'after_grading' => $attempt->isGraded(),
            'after_all_submissions' => $this->allSubmissionsComplete($exam),
            'after_date' => $exam->results_release_date && now()->gte($exam->results_release_date),
            'never' => false,
            default => false,
        };
    }

    protected function allSubmissionsComplete(Exam $exam): bool
    {
        $totalEnrolled = $exam->class->enrollments()->where('status', 'active')->count();
        $totalSubmitted = ExamAttempt::ofExam($exam->id)->submitted()->count();

        return $totalSubmitted >= $totalEnrolled;
    }
}
