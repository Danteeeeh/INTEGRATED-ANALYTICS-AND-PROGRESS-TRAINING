<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\GradeItem;

/**
 * Deterministic "On Track / At Risk" signals for the gradebook.
 *
 * There is no ML here on purpose. Every signal is a threshold the school can
 * read, argue with and change, and each one reports *why* it fired so the
 * instructor sees which part of the grade is dragging the student down rather
 * than an unexplained badge.
 */
class StudentRiskService
{
    /** A component scoring below this share of its own scale is a warning. */
    public const COMPONENT_FLOOR = 50.0;

    /** Attendance below this is a warning when attendance is graded. */
    public const ATTENDANCE_FLOOR = 75.0;

    /** Attendance below this is a CRITICAL warning (triggers risk even for high final grades). */
    public const ATTENDANCE_CRITICAL_FLOOR = 50.0;

    /** Lesson progress below this is a warning. */
    public const PROGRESS_FLOOR = 50.0;

    /** Students with final grade above this are "safe" unless attendance is critical. */
    public const SAFE_FINAL_FLOOR = 75.0;

    public function __construct(
        private GradeBreakdownService $breakdown,
        private ContentProgressService $progress,
    ) {}

    /**
     * Status for one student's breakdown.
     *
     * @param  array<string, mixed>  $summary  One row from GradeBreakdownService::forClass()
     * @return array{status: string, label: string, tone: string, reasons: array<int, string>, has_data: bool}
     */
    public function evaluate(ClassModel $class, array $summary): array
    {
        $passing = (float) config('lms.passing_grade', 60);

        if (! ($summary['is_graded'] ?? false)) {
            $missing = (int) ($summary['missing_items'] ?? 0);
            $hasPartial = ($summary['provisional_grade'] ?? null) !== null;

            if ($hasPartial) {
                return [
                    'status' => 'incomplete',
                    'label' => 'Incomplete',
                    'tone' => 'muted',
                    'reasons' => [sprintf(
                        '%d graded item%s outstanding — no final grade yet.',
                        $missing,
                        $missing === 1 ? ' is' : 's are'
                    )],
                    'has_data' => true,
                ];
            }

            return [
                'status' => 'pending',
                'label' => 'Pending',
                'tone' => 'muted',
                'reasons' => ['No graded work yet.'],
                'has_data' => false,
            ];
        }

        $reasons = [];
        $final = (float) $summary['final_grade'];
        $isHighPerformer = $final >= self::SAFE_FINAL_FLOOR;

        // 1. Any weighted component below half its scale — but don't flag for
        //    high performers unless the component is critically low.
        foreach (($summary['components'] ?? []) as $type => $component) {
            if (! ($component['is_graded'] ?? false) || $component['percent'] === null) {
                continue;
            }

            $componentPercent = (float) $component['percent'];
            $isCritical = $componentPercent < (self::COMPONENT_FLOOR / 2); // below 25%

            if ($componentPercent < self::COMPONENT_FLOOR && ($component['weight'] ?? 0) > 0) {
                if (! $isHighPerformer || $isCritical) {
                    $reasons[] = sprintf(
                        '%s is at %s%% (below %s%%).',
                        $component['label'],
                        number_format($componentPercent, 1),
                        number_format(self::COMPONENT_FLOOR, 0)
                    );
                }
            }
        }

        // 2. Attendance — only when the instructor graded it, and only when
        //    there is actually something to measure.
        $attendance = ($summary['components'][GradeItem::TYPE_ATTENDANCE] ?? null);

        if ($attendance && ($attendance['is_graded'] ?? false) && $attendance['percent'] !== null) {
            $attendancePercent = (float) $attendance['percent'];
            $isCriticalAttendance = $attendancePercent < self::ATTENDANCE_CRITICAL_FLOOR;

            if ($attendancePercent < self::ATTENDANCE_FLOOR) {
                if (! $isHighPerformer || $isCriticalAttendance) {
                    $reasons[] = sprintf(
                        'Attendance is at %s%% (below %s%%).',
                        number_format($attendancePercent, 1),
                        number_format(self::ATTENDANCE_FLOOR, 0)
                    );
                }
            }
        }

        // 3. Lesson progress.
        $progress = $this->lessonProgress($class->id, (int) $summary['student_id']);

        if ($progress['has_content'] && $progress['percent'] < self::PROGRESS_FLOOR) {
            $reasons[] = sprintf(
                'Lesson progress is at %s%% (below %s%%).',
                number_format($progress['percent'], 1),
                number_format(self::PROGRESS_FLOOR, 0)
            );
        }

        // 4. The final grade itself.
        if ($final < $passing) {
            $reasons[] = sprintf(
                'Final grade %s%% is below the %s%% passing mark.',
                number_format($final, 2),
                number_format($passing, 0)
            );
        }

        $hasRisk = $reasons !== [];

        return [
            'status' => $hasRisk ? 'at_risk' : 'passed',
            'label' => $hasRisk ? 'At Risk' : ($final >= $passing ? 'Passed' : 'Failed'),
            'tone' => $hasRisk ? 'danger' : ($final >= $passing ? 'success' : 'warning'),
            'reasons' => $reasons,
            'has_data' => true,
        ];
    }

    /**
     * Evaluate a whole class at once.
     *
     * @param  array<int, array<string, mixed>>  $summaries
     * @return array<int, array<string, mixed>>
     */
    public function evaluateAll(ClassModel $class, array $summaries): array
    {
        $results = [];

        foreach ($summaries as $studentId => $summary) {
            $results[$studentId] = $this->evaluate($class, $summary);
        }

        return $results;
    }

    /**
     * How much of the class content this student has worked through.
     *
     * Modules hang off a course rather than a class, so this reuses
     * ContentProgressService's live calculation (which already knows how to
     * average only the categories that have content) rather than inventing a
     * second, disagreeing definition of "progress".
     *
     * @return array{has_content: bool, percent: float, completed: int, total: int}
     */
    public function lessonProgress(int $classId, int $studentId): array
    {
        $class = ClassModel::find($classId);

        if (! $class?->course) {
            return ['has_content' => false, 'percent' => 0.0, 'completed' => 0, 'total' => 0];
        }

        $progress = $this->progress->calculateCourseLiveProgress(
            $class->course,
            $studentId,
            $classId,
        );

        $totals = $progress['totals'] ?? [];
        $completed = $progress['completed'] ?? [];

        $total = (int) array_sum($totals);
        $done = (int) array_sum($completed);

        return [
            'has_content' => $total > 0,
            'percent' => round((float) ($progress['overall'] ?? 0), 2),
            'completed' => $done,
            'total' => $total,
        ];
    }

    /**
     * Counts for the gradebook header.
     *
     * @param  array<int, array<string, mixed>>  $risk
     * @return array<int, int>
     */
    public function tally(array $risk): array
    {
        return [
            'total' => count($risk),
            'at_risk' => collect($risk)->where('status', 'at_risk')->count(),
            'passed' => collect($risk)->where('status', 'passed')->count(),
            'pending' => collect($risk)->where('status', 'pending')->count(),
        ];
    }
}