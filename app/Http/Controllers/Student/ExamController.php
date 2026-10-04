<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(
        protected ExamService $examService
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

        $exams = Exam::published()
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->with(['attempts' => function ($q) use ($studentId) {
                $q->ofStudent($studentId)->latest();
            }])
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

        return view('student.exams.show', compact('course', 'exam', 'enrollment', 'myAttempts', 'inProgressAttempt'));
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

        return view('student.exams.confirm', compact('course', 'exam', 'enrollment'));
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

            return view('student.exams.attempt', compact('course', 'exam', 'enrollment', 'inProgress'));
        }

        $attemptCount = ExamAttempt::ofExam($exam->id)->ofStudent($studentId)->count();
        if ($exam->attempt_limit && $attemptCount >= $exam->attempt_limit) {
            return redirect()->route('student.courses.exams.show', [$course, $exam])
                ->with('error', 'You have reached the maximum number of attempts for this exam.');
        }

        $attempt = $this->examService->startAttempt($exam);
        $inProgress = $attempt;

        return view('student.exams.attempt', compact('course', 'exam', 'enrollment', 'inProgress'));
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
            ->firstOrFail();

        if ($exam->duration_minutes && $exam->auto_submit_on_timeout) {
            $deadline = $attempt->started_at->addMinutes($exam->duration_minutes);
            if (now()->gte($deadline)) {
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
        // Get current answers from the attempt
        $answers = [];
        foreach ($attempt->answers as $answer) {
            $answers[$answer->question_id] = [
                'text' => $answer->answer_text,
                'data' => $answer->answer_data,
            ];
        }

        $attempt = $this->examService->submitAttempt($attempt, $answers);

        return redirect()->route('student.courses.exams.attempts.show', [$course, $exam, $attempt])
            ->with('warning', 'Exam time expired. Answers were auto-submitted.');
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
