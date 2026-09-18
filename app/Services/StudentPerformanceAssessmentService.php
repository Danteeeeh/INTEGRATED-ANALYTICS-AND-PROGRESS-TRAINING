<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Grade;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentPerformanceAssessmentService
{
    /**
     * Build an explainable learner assessment from existing LMS signals.
     *
     * This is intentionally deterministic and provider-neutral. It does not
     * change grades, submissions, attempts, or progress records.
     *
     * @return array{
     *   status: string,
     *   label: string,
     *   risk_score: int,
     *   confidence: int,
     *   data_coverage: int,
     *   summary: string,
     *   signals: array<int, array{key:string,label:string,value:string,status:string,detail:string}>,
     *   reasons: array<int, string>,
     *   recommendations: array<int, array{title:string,detail:string,icon:string}>
     * }
     */
    public function assess(int $studentId, Collection $activeClassIds, float|int $overallProgress, int $learningStreak): array
    {
        $classIds = $activeClassIds->filter()->values();
        $signalCount = 0;
        $riskScore = 0;
        $reasons = [];
        $recommendations = [];
        $signals = [];

        $quizAttempts = QuizAttempt::query()
            ->where('student_id', $studentId)
            ->whereIn('status', [
                QuizAttempt::STATUS_SUBMITTED,
                QuizAttempt::STATUS_AUTO_SUBMITTED,
                QuizAttempt::STATUS_GRADED,
            ])
            ->get();

        $quizScores = $quizAttempts->map(function (QuizAttempt $attempt): ?float {
            $score = $attempt->score_percent;
            if ($score === null && $attempt->score !== null) {
                $score = (float) $attempt->score;
            }

            return $score === null ? null : (float) $score;
        })->filter(fn (?float $score): bool => $score !== null)->values();

        if ($quizScores->isNotEmpty()) {
            $signalCount++;
            $quizAverage = round((float) $quizScores->avg(), 1);
            $quizStatus = $quizAverage < 60 ? 'risk' : ($quizAverage < 75 ? 'watch' : 'good');
            $signals[] = [
                'key' => 'quiz_scores',
                'label' => 'Quiz performance',
                'value' => $quizAverage.'%',
                'status' => $quizStatus,
                'detail' => $quizScores->count().' completed attempt'.($quizScores->count() === 1 ? '' : 's'),
            ];

            if ($quizAverage < 60) {
                $riskScore += 25;
                $reasons[] = 'Quiz scores are below the 60% support threshold.';
                $recommendations[] = [
                    'title' => 'Review quiz topics',
                    'detail' => 'Revisit the related lessons and try the next available practice activity.',
                    'icon' => 'fa-book-open',
                ];
            } elseif ($quizAverage < 75) {
                $riskScore += 12;
                $reasons[] = 'Quiz performance is below the 75% target range.';
                $recommendations[] = [
                    'title' => 'Strengthen quiz preparation',
                    'detail' => 'Review missed concepts before starting another quiz attempt.',
                    'icon' => 'fa-lightbulb',
                ];
            }
        }

        $publishedAssignments = Assignment::query()
            ->whereIn('class_id', $classIds)
            ->where('status', Assignment::STATUS_PUBLISHED)
            ->get(['id', 'due_date']);

        if ($publishedAssignments->isNotEmpty()) {
            $signalCount++;
            $assignmentIds = $publishedAssignments->pluck('id');
            $submittedIds = AssignmentSubmission::query()
                ->where('student_id', $studentId)
                ->whereIn('assignment_id', $assignmentIds)
                ->whereIn('status', [
                    AssignmentSubmission::STATUS_SUBMITTED,
                    AssignmentSubmission::STATUS_GRADED,
                    AssignmentSubmission::STATUS_RETURNED,
                    AssignmentSubmission::STATUS_RESUBMITTED,
                ])
                ->distinct()
                ->pluck('assignment_id');

            $assignmentTotal = $publishedAssignments->count();
            $submittedCount = $submittedIds->count();
            $assignmentCompletion = (int) round(($submittedCount / $assignmentTotal) * 100);
            $overdueCount = $publishedAssignments
                ->filter(fn (Assignment $assignment): bool => $assignment->due_date !== null && $assignment->due_date->isPast())
                ->whereNotIn('id', $submittedIds)
                ->count();
            $assignmentStatus = $overdueCount > 0 || $assignmentCompletion < 50
                ? 'risk'
                : ($assignmentCompletion < 80 ? 'watch' : 'good');

            $signals[] = [
                'key' => 'assignments',
                'label' => 'Assignment progress',
                'value' => $assignmentCompletion.'%',
                'status' => $assignmentStatus,
                'detail' => $submittedCount.'/'.$assignmentTotal.' submitted'.($overdueCount > 0 ? ' · '.$overdueCount.' overdue' : ''),
            ];

            if ($overdueCount > 0) {
                $riskScore += min(30, 15 + ($overdueCount * 5));
                $reasons[] = $overdueCount.' assignment'.($overdueCount === 1 ? ' is' : 's are').' overdue without a completed submission.';
                $recommendations[] = [
                    'title' => 'Complete overdue work',
                    'detail' => 'Start with the oldest missing assignment to recover your learning momentum.',
                    'icon' => 'fa-file-pen',
                ];
            } elseif ($assignmentCompletion < 80) {
                $riskScore += 12;
                $reasons[] = 'Assignment submissions are below the 80% progress target.';
                $recommendations[] = [
                    'title' => 'Plan the next submission',
                    'detail' => 'Choose the next due assignment and set a short completion target.',
                    'icon' => 'fa-calendar-check',
                ];
            }
        }

        $progressPercent = max(0, min(100, (float) $overallProgress));
        if ($classIds->isNotEmpty()) {
            $signalCount++;
            $progressStatus = $progressPercent < 35 ? 'risk' : ($progressPercent < 70 ? 'watch' : 'good');
            $signals[] = [
                'key' => 'course_progress',
                'label' => 'Course progress',
                'value' => round($progressPercent).'%',
                'status' => $progressStatus,
                'detail' => 'Average progress across active courses',
            ];

            if ($progressPercent < 35) {
                $riskScore += 20;
                $reasons[] = 'Course progress is below the 35% support threshold.';
                $recommendations[] = [
                    'title' => 'Continue the next lesson',
                    'detail' => 'Open your most recently accessed course and complete one lesson today.',
                    'icon' => 'fa-play',
                ];
            } elseif ($progressPercent < 70) {
                $riskScore += 8;
                $recommendations[] = [
                    'title' => 'Keep a steady pace',
                    'detail' => 'A short, regular study session can move this course toward completion.',
                    'icon' => 'fa-route',
                ];
            }
        }

        $recentActivity = LessonProgress::query()
            ->where('student_id', $studentId)
            ->where('updated_at', '>=', Carbon::now()->subDays(14))
            ->exists();

        if ($classIds->isNotEmpty() || $quizAttempts->isNotEmpty()) {
            $signalCount++;
            $activityStatus = $recentActivity || $learningStreak > 0 ? 'good' : 'watch';
            $signals[] = [
                'key' => 'learning_activity',
                'label' => 'Learning activity',
                'value' => $recentActivity || $learningStreak > 0 ? 'Active' : 'Quiet',
                'status' => $activityStatus,
                'detail' => $learningStreak > 0 ? $learningStreak.'-day learning streak' : 'Last 14 days',
            ];

            if (! $recentActivity && $learningStreak === 0) {
                $riskScore += 12;
                $reasons[] = 'No recent lesson activity or learning streak was detected.';
                $recommendations[] = [
                    'title' => 'Restart your learning rhythm',
                    'detail' => 'Spend 15 minutes in your course today to rebuild a consistent routine.',
                    'icon' => 'fa-bolt',
                ];
            }
        }

        $releasedGrades = Grade::query()
            ->where('student_id', $studentId)
            ->whereHas('item', fn ($query) => $query->where('is_released', true))
            ->whereNotNull('score_percent')
            ->pluck('score_percent');

        if ($releasedGrades->isNotEmpty()) {
            $signalCount++;
            $gradeAverage = round((float) $releasedGrades->avg(), 1);
            $gradeStatus = $gradeAverage < 60 ? 'risk' : ($gradeAverage < 75 ? 'watch' : 'good');
            $signals[] = [
                'key' => 'released_grades',
                'label' => 'Released grades',
                'value' => $gradeAverage.'%',
                'status' => $gradeStatus,
                'detail' => $releasedGrades->count().' released grade'.($releasedGrades->count() === 1 ? '' : 's'),
            ];

            if ($gradeAverage < 60) {
                $riskScore += 20;
                $reasons[] = 'Released grades indicate an area that may need additional support.';
                $recommendations[] = [
                    'title' => 'Review feedback',
                    'detail' => 'Use the latest released feedback to choose the next topic to practice.',
                    'icon' => 'fa-comment-dots',
                ];
            }
        }

        $coverage = (int) round(($signalCount / 5) * 100);
        $riskScore = max(0, min(100, $riskScore));

        if ($signalCount < 2) {
            $status = 'insufficient_data';
            $label = 'BUILDING A BASELINE';
            $summary = 'Complete a few learning activities and the assessment will become more precise.';
            $reasons = ['There is not enough recent performance data to classify risk reliably.'];
            $recommendations = [[
                'title' => 'Complete your first activity',
                'detail' => 'Open a lesson, submit an assignment, or complete a quiz to build your learning baseline.',
                'icon' => 'fa-seedling',
            ]];
        } else {
            $status = $riskScore >= 40 ? 'at_risk' : 'on_track';
            $label = $status === 'at_risk' ? 'AT RISK' : 'ON TRACK';
            $summary = $status === 'at_risk'
                ? 'A few signals suggest that focused support could help you regain momentum.'
                : 'Your current learning signals show steady progress across the course activities.';

            if ($status === 'on_track' && $reasons === []) {
                $reasons[] = 'Your available performance signals are within the expected learning range.';
            }

            if ($status === 'on_track' && $recommendations === []) {
                $recommendations[] = [
                    'title' => 'Maintain your momentum',
                    'detail' => 'Keep following your course schedule and check upcoming work regularly.',
                    'icon' => 'fa-compass',
                ];
            }
        }

        return [
            'status' => $status,
            'label' => $label,
            'risk_score' => $riskScore,
            'confidence' => $coverage,
            'data_coverage' => $coverage,
            'summary' => $summary,
            'signals' => $signals,
            'reasons' => array_values(array_unique($reasons)),
            'recommendations' => array_slice($recommendations, 0, 3),
        ];
    }
}
