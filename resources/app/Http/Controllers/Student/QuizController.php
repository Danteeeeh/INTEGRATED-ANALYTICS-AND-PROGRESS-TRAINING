<?php

namespace App\Http\Controllers\Student;

use App\Services\QuestionGrader;
use App\Services\QuestionSnapshotService;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\SectionModuleAssignment;
use App\Models\StudentModuleAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Course $course): View
    {
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

        // Get module IDs assigned to this student's section
        $assignedModuleIds = SectionModuleAssignment::active()
            ->byCourse($course->id)
            ->bySection($enrollment->class->section_id)
            ->pluck('module_id');

        $quizzes = Quiz::published()
            ->where(function ($q) use ($course, $assignedModuleIds) {
                // Class-level quizzes (not in modules)
                $q->whereHas('class', function ($q2) use ($course) {
                    $q2->where('course_id', $course->id);
                })
                // Module-level quizzes (only from assigned modules)
                ->orWhere(function ($q2) use ($course, $assignedModuleIds) {
                    $q2->whereHas('module', function ($q3) use ($course, $assignedModuleIds) {
                        $q3->where('course_id', $course->id)
                            ->whereIn('id', $assignedModuleIds)
                            ->published();
                    });
                })
                // Lesson-level quizzes (only from lessons in assigned modules)
                ->orWhere(function ($q2) use ($course, $assignedModuleIds) {
                    $q2->whereHas('lesson.module', function ($q3) use ($course, $assignedModuleIds) {
                        $q3->where('course_id', $course->id)
                            ->whereIn('id', $assignedModuleIds)
                            ->published();
                    });
                });
            })
            ->with(['attempts' => function ($q) use ($studentId) {
                $q->ofStudent($studentId)->latest();
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('student.quizzes.index', compact('course', 'quizzes', 'enrollment'));
    }

    public function show(Course $course, Quiz $quiz): View
    {
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

        $quiz->load('questions.choices');

        $myAttempts = QuizAttempt::ofQuiz($quiz->id)
            ->ofStudent($studentId)
            ->orderBy('attempt_number', 'desc')
            ->paginate(5);

        $inProgressAttempt = QuizAttempt::ofQuiz($quiz->id)
            ->ofStudent($studentId)
            ->inProgress()
            ->first();

        $isOverdue = $quiz->isOverdueForStudent($studentId);
        $effectiveDeadline = $quiz->getEffectiveDeadlineForStudent($studentId);

        return view('student.quizzes.show', compact('course', 'quiz', 'enrollment', 'myAttempts', 'inProgressAttempt', 'isOverdue', 'effectiveDeadline'));
    }

    public function startAttempt(Course $course, Quiz $quiz): View|RedirectResponse
    {
        $studentId = auth()->id();

        \Log::info('Quiz attempt start', [
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'course_id' => $course->id,
            'student_id' => $studentId,
        ]);

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            \Log::error('Quiz attempt: No enrollment found', [
                'student_id' => $studentId,
                'course_id' => $course->id,
            ]);
            abort(403);
        }

        if (! $quiz->available()) {
            \Log::error('Quiz attempt: Quiz not available', [
                'quiz_id' => $quiz->id,
                'status' => $quiz->status,
                'availability_from' => $quiz->availability_from,
                'availability_until' => $quiz->availability_until,
            ]);
            return redirect()->route('student.courses.quizzes.show', [$course, $quiz])
                ->with('error', 'This quiz is not currently available.');
        }

        if ($quiz->isOverdueForStudent($studentId)) {
            \Log::error('Quiz attempt: Quiz is overdue for student', [
                'quiz_id' => $quiz->id,
                'student_id' => $studentId,
                'availability_until' => $quiz->availability_until,
            ]);
            return redirect()->route('student.courses.quizzes.show', [$course, $quiz])
                ->with('error', 'This quiz is overdue and no longer available for attempts.');
        }

        $inProgress = QuizAttempt::ofQuiz($quiz->id)
            ->ofStudent($studentId)
            ->inProgress()
            ->first();

        if ($inProgress) {
            if ($quiz->time_limit_minutes && $quiz->auto_submit_on_timeout) {
                $deadline = $inProgress->started_at->addMinutes($quiz->time_limit_minutes);
                if (now()->gte($deadline)) {
                    return $this->autoSubmitAttempt($inProgress, $course, $quiz);
                }
            }

            // Check if the in-progress attempt has answers/questions
            $inProgress->load('answers.question', 'answers.selectedChoices');
            if ($inProgress->answers->isEmpty()) {
                \Log::error('Quiz attempt: In-progress attempt has no answers', [
                    'attempt_id' => $inProgress->id,
                    'quiz_id' => $quiz->id,
                ]);
                // Delete the invalid attempt and let them start fresh
                $inProgress->delete();
            } else {
                return view('student.quizzes.attempt', compact('course', 'quiz', 'enrollment', 'inProgress'));
            }
        }

        $attemptCount = QuizAttempt::ofQuiz($quiz->id)->ofStudent($studentId)->count();
        if ($quiz->attempt_limit && $attemptCount >= $quiz->attempt_limit) {
            return redirect()->route('student.courses.quizzes.show', [$course, $quiz])
                ->with('error', 'You have reached the maximum number of attempts.');
        }

        // Archived questions are retired from the bank: skip them for new
        // attempts while leaving existing attempts intact (§3).
        $questions = $quiz->questions()->with('choices')->availableForAttempts();
        if ($quiz->shuffle_questions) {
            $questions = $questions->inRandomOrder();
        }
        $questions = $questions->get();

        \Log::info('Quiz questions loaded', [
            'quiz_id' => $quiz->id,
            'questions_count' => $questions->count(),
        ]);

        if ($questions->isEmpty()) {
            \Log::error('Quiz attempt: No questions found', [
                'quiz_id' => $quiz->id,
            ]);
            return redirect()->route('student.courses.quizzes.show', [$course, $quiz])
                ->with('error', 'This quiz has no active questions yet. Please contact your instructor.');
        }

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $studentId,
            'attempt_number' => $attemptCount + 1,
            'started_at' => now(),
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
        ]);

        // Freeze each question as it is being sat (§14): later edits to the Test
        // Bank question must not alter this attempt.
        $snapshots = app(QuestionSnapshotService::class)->captureMany(
            $questions,
            $quiz->pointsByQuestionId()
        );

        foreach ($questions as $question) {
            QuizAnswer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'question_snapshot' => $snapshots[$question->id] ?? null,
            ]);
        }

        $attempt->load('answers.question.choices');
        $inProgress = $attempt;

        return view('student.quizzes.attempt', compact('course', 'quiz', 'enrollment', 'inProgress'));
    }

    public function storeAttempt(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
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

        $attempt = QuizAttempt::ofQuiz($quiz->id)
            ->ofStudent($studentId)
            ->inProgress()
            ->with(['answers.question', 'answers.selectedChoices'])
            ->firstOrFail();

        if ($quiz->time_limit_minutes && $quiz->auto_submit_on_timeout) {
            $deadline = $attempt->started_at->addMinutes($quiz->time_limit_minutes);
            if (now()->gte($deadline)) {
                return $this->autoSubmitAttempt($attempt, $course, $quiz);
            }
        }

        $answers = $request->input('answers', []);

        // Log for debugging
        \Log::info('Quiz submission attempt', [
            'quiz_id' => $quiz->id,
            'attempt_id' => $attempt->id,
            'student_id' => $studentId,
            'answers_received' => $answers,
            'answers_count' => count($answers),
        ]);
        $totalScore = 0;
        $totalPoints = 0;
        $grader = app(QuestionGrader::class);

        foreach ($attempt->answers as $quizAnswer) {
            $question = $quizAnswer->question;
            $snapshot = $quizAnswer->snapshotArray();

            $pivotPoints = $question?->quizzes->find($quiz->id)?->pivot->points
                ?? $snapshot['points']
                ?? $question?->default_points
                ?? 1;

            $answerData = $answers[$quizAnswer->question_id] ?? null;

            // Compares against the frozen question (§14), not today's Test Bank.
            $result = $grader->grade($answerData, $snapshot, $question, (float) $pivotPoints);

            // Keep the student's submission verbatim for the review screen.
            $quizAnswer->answer_text = is_array($answerData)
                ? json_encode(array_values($answerData))
                : $answerData;

            if ($result['manual']) {
                // Essay / no answer key: leave it for the instructor (§21).
                // It is also left out of the denominator so an unmarked essay
                // does not count as a wrong answer.
                $quizAnswer->is_correct = null;
                $quizAnswer->points_awarded = null;
                $quizAnswer->save();

                continue;
            }

            $quizAnswer->is_correct = $result['is_correct'];
            $quizAnswer->points_awarded = $result['points'];
            $quizAnswer->save();

            // Persist which choices the student selected for multiple-choice questions
            // so they are visible in the instructor's review screen (§14).
            $this->syncSelectedChoices($quizAnswer, $answerData, $question);

            $totalScore += $result['points'];
            $totalPoints += $pivotPoints;
        }

        $scorePercent = $totalPoints > 0 ? ($totalScore / $totalPoints) * 100 : 0;
        $hasPendingGrades = $this->hasPendingGrades($attempt);

        // Only a pass/fail verdict can be recorded once everything is marked;
        // otherwise the attempt stays undecided until the instructor grades.
        $isPassed = $totalPoints > 0 && ! $hasPendingGrades
            ? $scorePercent >= $quiz->passing_score_percent
            : null;
        $timeSpent = now()->diffInSeconds($attempt->started_at);

        // Ensure time_spent_seconds is never negative
        if ($timeSpent < 0) {
            $timeSpent = 0;
        }

        $attempt->update([
            'ended_at' => now(),
            'submitted_at' => now(),
            'time_spent_seconds' => $timeSpent,
            'score' => $totalScore,
            'score_percent' => round($scorePercent, 2),
            'is_passed' => $isPassed,
            'status' => QuizAttempt::STATUS_SUBMITTED,
        ]);

        return redirect()->route('student.courses.quizzes.attempts.show', [$course, $quiz, $attempt])
            ->with('status', 'Quiz submitted successfully!');
    }

    /**
     * Persist the student's chosen choices to the quiz_answer_choices pivot.
     *
     * @param  mixed  $answerData  Raw request value (array of choice IDs or single ID)
     */
    protected function syncSelectedChoices(QuizAnswer $quizAnswer, mixed $answerData, ?Question $question): void
    {
        // Use snapshot's question_type as fallback if question is null (trashed)
        $snapshot = $quizAnswer->snapshotArray();
        $qtype = $question?->question_type ?? $snapshot['question_type'] ?? null;

        if (! $qtype || ! in_array($qtype, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_MULTIPLE_ANSWER, Question::TYPE_TRUE_FALSE], true)) {
            return;
        }

        $selected = [];

        if (is_array($answerData)) {
            foreach ($answerData as $choiceId) {
                $selected[] = (int) $choiceId;
            }
        } elseif (is_numeric($answerData)) {
            $selected[] = (int) $answerData;
        } elseif (is_string($answerData) && str_starts_with($answerData, '[')) {
            $decoded = json_decode($answerData, true);
            if (is_array($decoded)) {
                foreach ($decoded as $choiceId) {
                    $selected[] = (int) $choiceId;
                }
            }
        }

        if ($selected !== []) {
            $quizAnswer->selectedChoices()->sync($selected);
        }
    }

    /**
     * Has the instructor still got work to do on this attempt?
     *
     * A row counts as pending only when the student actually submitted something
     * and no verdict was recorded — an unanswered row is simply a zero, not an
     * open question.
     */
    protected function hasPendingGrades(QuizAttempt $attempt): bool
    {
        return $attempt->answers()
            ->whereNull('is_correct')
            ->whereNotNull('answer_text')
            ->where('answer_text', '!=', '')
            ->exists();
    }

    protected function autoSubmitAttempt(QuizAttempt $attempt, Course $course, Quiz $quiz): RedirectResponse
    {
        // Ensure answers and questions are loaded for grading
        $attempt->loadMissing('answers.question', 'answers.selectedChoices');
        
        $totalScore = 0;
        $totalPoints = 0;
        $grader = app(QuestionGrader::class);

        foreach ($attempt->answers as $quizAnswer) {
            $question = $quizAnswer->question;
            $snapshot = $quizAnswer->snapshotArray();

            $pivotPoints = $question?->quizzes->find($quiz->id)?->pivot->points
                ?? $snapshot['points']
                ?? $question?->default_points
                ?? 1;

            // Stored submissions are scalar for one-answer types and JSON for
            // multiple answer; undo that before grading.
            $submitted = $quizAnswer->answer_text;

            if (is_string($submitted) && str_starts_with(ltrim($submitted), '[')) {
                $decoded = json_decode($submitted, true);

                if (is_array($decoded)) {
                    $submitted = $decoded;
                }
            }

            $result = $grader->grade($submitted, $snapshot, $question, (float) $pivotPoints);

            if ($result['manual']) {
                $quizAnswer->is_correct = null;
                $quizAnswer->points_awarded = null;
                $quizAnswer->save();

                continue;
            }

            $quizAnswer->is_correct = $result['is_correct'];
            $quizAnswer->points_awarded = $result['points'];
            $quizAnswer->save();

            // Persist which choices the student selected for multiple-choice questions
            // so they are visible in the instructor's review screen (§14).
            $this->syncSelectedChoices($quizAnswer, $submitted, $question);

            $totalScore += $result['points'];
            $totalPoints += $pivotPoints;
        }

        $scorePercent = $totalPoints > 0 ? ($totalScore / $totalPoints) * 100 : 0;
        $hasPendingGrades = $this->hasPendingGrades($attempt);
        $isPassed = $totalPoints > 0 && ! $hasPendingGrades
            ? $scorePercent >= $quiz->passing_score_percent
            : null;
        $timeSpent = $quiz->time_limit_minutes * 60;

        // Ensure time_spent_seconds is never negative
        if ($timeSpent < 0) {
            $timeSpent = 0;
        }

        $attempt->update([
            'ended_at' => $attempt->started_at->addMinutes($quiz->time_limit_minutes),
            'submitted_at' => now(),
            'time_spent_seconds' => $timeSpent,
            'score' => $totalScore,
            'score_percent' => round($scorePercent, 2),
            'is_passed' => $isPassed,
            'status' => QuizAttempt::STATUS_AUTO_SUBMITTED,
        ]);

        return redirect()->route('student.courses.quizzes.attempts.show', [$course, $quiz, $attempt])
            ->with('status', 'Quiz time expired. Answers were auto-submitted.');
    }

    public function showAttempt(Course $course, Quiz $quiz, QuizAttempt $attempt): View
    {
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

        if ($attempt->student_id !== $studentId) {
            abort(403);
        }

        $attempt->load('answers.question.choices', 'answers.selectedChoices');

        return view('student.quizzes.attempt_show', compact('course', 'quiz', 'attempt', 'enrollment'));
    }
}
