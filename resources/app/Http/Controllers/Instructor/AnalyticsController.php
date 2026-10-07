<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $instructorId = auth()->id();
        $classes = ClassModel::where('instructor_id', $instructorId)
            ->with('course')
            ->get();

        return view('instructor.analytics.index', compact('classes'));
    }

    public function quizAnalytics(Request $request): View
    {
        $instructorId = auth()->id();
        $classIds = ClassModel::where('instructor_id', $instructorId)->pluck('id');

        $query = Quiz::whereIn('class_id', $classIds);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $quizzes = $query->with(['class', 'attempts.student'])->get();

        $quizStats = [];
        foreach ($quizzes as $quiz) {
            $attempts = $quiz->attempts;
            $gradedAttempts = $attempts->where('status', 'graded');
            $passedAttempts = $attempts->where('is_passed', true);

            $quizStats[] = [
                'quiz' => $quiz,
                'total_attempts' => $attempts->count(),
                'graded_attempts' => $gradedAttempts->count(),
                'passed_attempts' => $passedAttempts->count(),
                'average_score' => $gradedAttempts->isNotEmpty() ? $gradedAttempts->avg('score_percent') : 0,
                'pass_rate' => $gradedAttempts->isNotEmpty() ? ($passedAttempts->count() / $gradedAttempts->count()) * 100 : 0,
                'average_time' => $attempts->isNotEmpty() ? $attempts->avg('time_spent_seconds') / 60 : 0,
            ];
        }

        return view('instructor.analytics.quizzes', compact('quizStats'));
    }

    public function examAnalytics(Request $request): View
    {
        $instructorId = auth()->id();
        $classIds = ClassModel::where('instructor_id', $instructorId)->pluck('id');

        $query = Exam::whereIn('class_id', $classIds);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $exams = $query->with(['class', 'attempts.student'])->get();

        $examStats = [];
        foreach ($exams as $exam) {
            $attempts = $exam->attempts;
            $gradedAttempts = $attempts->where('status', 'graded');
            $passedAttempts = $attempts->where('is_passed', true);
            $flaggedAttempts = $attempts->where('flagged_for_review', true);

            $examStats[] = [
                'exam' => $exam,
                'total_attempts' => $attempts->count(),
                'graded_attempts' => $gradedAttempts->count(),
                'passed_attempts' => $passedAttempts->count(),
                'flagged_attempts' => $flaggedAttempts->count(),
                'average_score' => $gradedAttempts->isNotEmpty() ? $gradedAttempts->avg('score_percent') : 0,
                'pass_rate' => $gradedAttempts->isNotEmpty() ? ($passedAttempts->count() / $gradedAttempts->count()) * 100 : 0,
                'average_time' => $attempts->isNotEmpty() ? $attempts->avg('time_spent_seconds') / 60 : 0,
            ];
        }

        return view('instructor.analytics.exams', compact('examStats'));
    }

    public function assignmentAnalytics(Request $request): View
    {
        $instructorId = auth()->id();
        $classIds = ClassModel::where('instructor_id', $instructorId)->pluck('id');

        $query = Assignment::whereIn('class_id', $classIds);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assignments = $query->with(['class', 'submissions.student'])->get();

        $assignmentStats = [];
        foreach ($assignments as $assignment) {
            $submissions = $assignment->submissions;
            $gradedSubmissions = $submissions->where('status', 'graded');
            $lateSubmissions = $submissions->where('is_late', true);

            $totalScore = 0;
            $gradedCount = 0;
            foreach ($gradedSubmissions as $submission) {
                $grade = $submission->grade;
                if ($grade && $grade->score_percent !== null) {
                    $totalScore += $grade->score_percent;
                    $gradedCount++;
                }
            }

            $assignmentStats[] = [
                'assignment' => $assignment,
                'total_submissions' => $submissions->count(),
                'graded_submissions' => $gradedSubmissions->count(),
                'late_submissions' => $lateSubmissions->count(),
                'average_score' => $gradedCount > 0 ? $totalScore / $gradedCount : 0,
                'submission_rate' => $assignment->submissions->count() > 0 ? 100 : 0,
            ];
        }

        return view('instructor.analytics.assignments', compact('assignmentStats'));
    }

    public function quizDetail(Quiz $quiz): View
    {
        $this->authorize('view', $quiz);

        $quiz->load(['class', 'questions', 'attempts.student', 'attempts.answers']);

        $attempts = $quiz->attempts;
        $gradedAttempts = $attempts->where('status', 'graded');

        // Score distribution
        $scoreRanges = [
            '90-100' => 0,
            '80-89' => 0,
            '70-79' => 0,
            '60-69' => 0,
            '0-59' => 0,
        ];

        foreach ($gradedAttempts as $attempt) {
            if ($attempt->score_percent >= 90) {
                $scoreRanges['90-100']++;
            } elseif ($attempt->score_percent >= 80) {
                $scoreRanges['80-89']++;
            } elseif ($attempt->score_percent >= 70) {
                $scoreRanges['70-79']++;
            } elseif ($attempt->score_percent >= 60) {
                $scoreRanges['60-69']++;
            } else {
                $scoreRanges['0-59']++;
            }
        }

        // Question performance
        $questionPerformance = [];
        foreach ($quiz->questions as $question) {
            $correctCount = 0;
            $totalAttempts = 0;

            foreach ($attempts as $attempt) {
                $answer = $attempt->answers->where('question_id', $question->id)->first();
                if ($answer) {
                    $totalAttempts++;
                    if ($answer->is_correct) {
                        $correctCount++;
                    }
                }
            }

            $questionPerformance[] = [
                'question' => $question,
                'correct_count' => $correctCount,
                'total_attempts' => $totalAttempts,
                'correct_rate' => $totalAttempts > 0 ? ($correctCount / $totalAttempts) * 100 : 0,
            ];
        }

        return view('instructor.analytics.quiz-detail', compact(
            'quiz',
            'attempts',
            'gradedAttempts',
            'scoreRanges',
            'questionPerformance'
        ));
    }

    public function examDetail(Exam $exam): View
    {
        $this->authorize('view', $exam);

        $exam->load(['class', 'questions', 'attempts.student', 'attempts.answers']);

        $attempts = $exam->attempts;
        $gradedAttempts = $attempts->where('status', 'graded');
        $flaggedAttempts = $attempts->where('flagged_for_review', true);

        // Score distribution
        $scoreRanges = [
            '90-100' => 0,
            '80-89' => 0,
            '70-79' => 0,
            '60-69' => 0,
            '0-59' => 0,
        ];

        foreach ($gradedAttempts as $attempt) {
            if ($attempt->score_percent >= 90) {
                $scoreRanges['90-100']++;
            } elseif ($attempt->score_percent >= 80) {
                $scoreRanges['80-89']++;
            } elseif ($attempt->score_percent >= 70) {
                $scoreRanges['70-79']++;
            } elseif ($attempt->score_percent >= 60) {
                $scoreRanges['60-69']++;
            } else {
                $scoreRanges['0-59']++;
            }
        }

        // Question performance
        $questionPerformance = [];
        foreach ($exam->questions as $question) {
            $correctCount = 0;
            $totalAttempts = 0;

            foreach ($attempts as $attempt) {
                $answer = $attempt->answers->where('question_id', $question->id)->first();
                if ($answer) {
                    $totalAttempts++;
                    if ($answer->is_correct) {
                        $correctCount++;
                    }
                }
            }

            $questionPerformance[] = [
                'question' => $question,
                'correct_count' => $correctCount,
                'total_attempts' => $totalAttempts,
                'correct_rate' => $totalAttempts > 0 ? ($correctCount / $totalAttempts) * 100 : 0,
            ];
        }

        return view('instructor.analytics.exam-detail', compact(
            'exam',
            'attempts',
            'gradedAttempts',
            'flaggedAttempts',
            'scoreRanges',
            'questionPerformance'
        ));
    }

    public function assignmentDetail(Assignment $assignment): View
    {
        $this->authorize('view', $assignment);

        $assignment->load(['class', 'submissions.student', 'submissions.files', 'submissions.rubricAssessments']);

        $submissions = $assignment->submissions;
        $gradedSubmissions = $submissions->where('status', 'graded');
        $lateSubmissions = $submissions->where('is_late', true);

        // Score distribution
        $scoreRanges = [
            '90-100' => 0,
            '80-89' => 0,
            '70-79' => 0,
            '60-69' => 0,
            '0-59' => 0,
        ];

        foreach ($gradedSubmissions as $submission) {
            $grade = $submission->grade;
            if ($grade && $grade->score_percent !== null) {
                if ($grade->score_percent >= 90) {
                    $scoreRanges['90-100']++;
                } elseif ($grade->score_percent >= 80) {
                    $scoreRanges['80-89']++;
                } elseif ($grade->score_percent >= 70) {
                    $scoreRanges['70-79']++;
                } elseif ($grade->score_percent >= 60) {
                    $scoreRanges['60-69']++;
                } else {
                    $scoreRanges['0-59']++;
                }
            }
        }

        return view('instructor.analytics.assignment-detail', compact(
            'assignment',
            'submissions',
            'gradedSubmissions',
            'lateSubmissions',
            'scoreRanges'
        ));
    }
}
