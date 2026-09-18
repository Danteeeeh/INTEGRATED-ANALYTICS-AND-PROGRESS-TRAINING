<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
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

        $quizzes = Quiz::published()
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
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

        return view('student.quizzes.show', compact('course', 'quiz', 'enrollment', 'myAttempts', 'inProgressAttempt'));
    }

    public function startAttempt(Course $course, Quiz $quiz): View|RedirectResponse
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

        if (! $quiz->available()) {
            return redirect()->route('student.courses.quizzes.show', [$course, $quiz])
                ->with('error', 'This quiz is not currently available.');
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

            return view('student.quizzes.attempt', compact('course', 'quiz', 'enrollment', 'inProgress'));
        }

        $attemptCount = QuizAttempt::ofQuiz($quiz->id)->ofStudent($studentId)->count();
        if ($quiz->attempt_limit && $attemptCount >= $quiz->attempt_limit) {
            return redirect()->route('student.courses.quizzes.show', [$course, $quiz])
                ->with('error', 'You have reached the maximum number of attempts.');
        }

        $questions = $quiz->questions()->with('choices');
        if ($quiz->shuffle_questions) {
            $questions = $questions->inRandomOrder();
        }
        $questions = $questions->get();

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $studentId,
            'attempt_number' => $attemptCount + 1,
            'started_at' => now(),
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
        ]);

        foreach ($questions as $question) {
            QuizAnswer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $question->id,
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
            ->firstOrFail();

        if ($quiz->time_limit_minutes && $quiz->auto_submit_on_timeout) {
            $deadline = $attempt->started_at->addMinutes($quiz->time_limit_minutes);
            if (now()->gte($deadline)) {
                return $this->autoSubmitAttempt($attempt, $course, $quiz);
            }
        }

        $answers = $request->input('answers', []);
        $totalScore = 0;
        $totalPoints = 0;

        foreach ($attempt->answers as $quizAnswer) {
            $question = $quizAnswer->question;
            $pivotPoints = $question->quizzes->find($quiz->id)?->pivot->points ?? $question->default_points ?? 1;
            $totalPoints += $pivotPoints;

            $answerData = $answers[$question->id] ?? null;
            $isCorrect = false;
            $pointsAwarded = 0;

            if ($question->question_type === Question::TYPE_MULTIPLE_CHOICE || $question->question_type === Question::TYPE_TRUE_FALSE) {
                $correctChoice = $question->choices->where('is_correct', true)->first();
                if ($answerData && $correctChoice && $answerData == $correctChoice->id) {
                    $isCorrect = true;
                    $pointsAwarded = $pivotPoints;
                }
                $quizAnswer->answer_text = $answerData;
            } elseif ($question->question_type === Question::TYPE_MULTIPLE_ANSWER) {
                $selectedIds = is_array($answerData) ? $answerData : [];
                $correctIds = $question->choices->where('is_correct', true)->pluck('id')->toArray();
                sort($selectedIds);
                sort($correctIds);
                if ($selectedIds === $correctIds) {
                    $isCorrect = true;
                    $pointsAwarded = $pivotPoints;
                }
                $quizAnswer->answer_text = json_encode($selectedIds);
            } else {
                $quizAnswer->answer_text = is_array($answerData) ? json_encode($answerData) : $answerData;
            }

            $quizAnswer->is_correct = $isCorrect;
            $quizAnswer->points_awarded = $pointsAwarded;
            $quizAnswer->save();

            $totalScore += $pointsAwarded;
        }

        $scorePercent = $totalPoints > 0 ? ($totalScore / $totalPoints) * 100 : 0;
        $isPassed = $scorePercent >= $quiz->passing_score_percent;
        $timeSpent = now()->diffInSeconds($attempt->started_at);

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

    protected function autoSubmitAttempt(QuizAttempt $attempt, Course $course, Quiz $quiz): RedirectResponse
    {
        $totalScore = 0;
        $totalPoints = 0;

        foreach ($attempt->answers as $quizAnswer) {
            $question = $quizAnswer->question;
            $pivotPoints = $question->quizzes->find($quiz->id)?->pivot->points ?? $question->default_points ?? 1;
            $totalPoints += $pivotPoints;

            if ($quizAnswer->answer_text) {
                if ($question->question_type === Question::TYPE_MULTIPLE_CHOICE || $question->question_type === Question::TYPE_TRUE_FALSE) {
                    $correctChoice = $question->choices->where('is_correct', true)->first();
                    if ($correctChoice && $quizAnswer->answer_text == $correctChoice->id) {
                        $quizAnswer->is_correct = true;
                        $quizAnswer->points_awarded = $pivotPoints;
                        $totalScore += $pivotPoints;
                    }
                } elseif ($question->question_type === Question::TYPE_MULTIPLE_ANSWER) {
                    $selectedIds = json_decode($quizAnswer->answer_text, true) ?: [];
                    $correctIds = $question->choices->where('is_correct', true)->pluck('id')->toArray();
                    sort($selectedIds);
                    sort($correctIds);
                    if ($selectedIds === $correctIds) {
                        $quizAnswer->is_correct = true;
                        $quizAnswer->points_awarded = $pivotPoints;
                        $totalScore += $pivotPoints;
                    }
                }
            }
            $quizAnswer->save();
        }

        $scorePercent = $totalPoints > 0 ? ($totalScore / $totalPoints) * 100 : 0;
        $isPassed = $scorePercent >= $quiz->passing_score_percent;
        $timeSpent = $quiz->time_limit_minutes * 60;

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

        $attempt->load('answers.question.choices');

        return view('student.quizzes.attempt_show', compact('course', 'quiz', 'attempt', 'enrollment'));
    }
}
