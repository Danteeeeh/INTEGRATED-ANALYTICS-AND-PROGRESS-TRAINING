<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeConfiguration;
use App\Models\GradeItem;

/**
 * Turns the instructor's grading configuration into a per-student breakdown.
 *
 * Two shapes of grade exist in this app and both must stay correct:
 *
 *   1. No configuration  → GradeItem.factor is the absolute weight, exactly as
 *      before. Every existing class keeps its current numbers.
 *   2. With a valid configuration → the component weight is authoritative and
 *      GradeItem.factor only ranks items *within* their component, so adding a
 *      second quiz cannot silently change how much the exam is worth.
 */
class GradeBreakdownService
{
    public function __construct(private GradeService $grades) {}

    /**
     * Per-student, per-component percentages plus the weighted final grade.
     *
     * @param  array<int>|null  $studentIds
     * @return array<int, array{
     *     student_id: int,
     *     final_grade: float,
     *     is_graded: bool,
     *     letter_grade: string,
     *     components: array<string, array{percent: float, weight: float, earned: float, possible: float, graded_items: int, is_graded: bool}>,
     *     item_breakdown: array<int, array{title: string, item_type: string, points: float|null, max_points: float, is_graded: bool}>
     * }>
     */
    public function forClass(ClassModel $class, ?array $studentIds = null): array
    {
        $configuration = $class->activeGradeConfiguration();

        $items = GradeItem::where('class_id', $class->id)
            ->where('is_released', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $studentIds ??= Enrollment::where('class_id', $class->id)
            ->countable()
            ->pluck('student_id')
            ->unique()
            ->values()
            ->all();

        $grades = Grade::whereIn('student_id', $studentIds)
            ->whereHas('item', fn ($q) => $q->where('class_id', $class->id)->where('is_released', true))
            ->with('item:id,title,item_type,max_points,factor,class_id')
            ->get()
            ->groupBy('student_id');

        $itemsById = $items->keyBy('id');

        $results = [];

        foreach ($studentIds as $studentId) {
            $studentGrades = $grades->get($studentId, collect());

            $results[$studentId] = $configuration
                ? $this->configuredSummary($configuration, $items, $itemsById, $studentGrades, $class->id, $studentId)
                : $this->legacySummary($items, $itemsById, $studentGrades, $class->id, $studentId);
        }

        return $results;
    }

    /**
     * Configured path: component weights decide the final grade.
     *
     * @param  \Illuminate\Support\Collection<int, GradeItem>  $items
     * @param  \Illuminate\Support\Collection<int, GradeItem>  $itemsById
     * @param  \Illuminate\Support\Collection<int, Grade>  $studentGrades
     * @return array<string, mixed>
     */
    protected function configuredSummary(
        GradeConfiguration $configuration,
        $items,
        $itemsById,
        $studentGrades,
        int $classId,
        int $studentId
    ): array {
        $weights = $configuration->enabledWeights();

        $components = [];
        $final = 0.0;
        $anyGraded = false;
        $weightedTotal = 0.0;

        foreach ($weights as $type => $weight) {
            $group = $studentGrades
                ->filter(fn ($g) => ($g->item?->item_type ?? $itemsById->get($g->grade_item_id)?->item_type) === $type);

            $percent = $this->percentOf($group);

            // A component with no graded work contributes nothing rather than a
            // zero, otherwise an ungraded exam would silently read as 0%.
            if ($group->isNotEmpty()) {
                $final += $percent * ($weight / GradeConfiguration::REQUIRED_TOTAL);
                $weightedTotal += $weight;
                $anyGraded = true;
            }

            $components[$type] = [
                'label' => GradeConfiguration::COMPONENTS[$type]['label'] ?? ucfirst($type),
                'percent' => $group->isEmpty() ? null : round($percent, 2),
                'weight' => round($weight, 2),
                'earned' => round((float) $group->sum(fn ($g) => (float) ($g->points ?? 0)), 2),
                'possible' => round((float) $group->sum(fn ($g) => (float) ($g->item?->max_points ?? 0)), 2),
                'graded_items' => $group->count(),
                'is_graded' => $group->isNotEmpty(),
            ];
        }

        // Attendance may be a weighted component but has no GradeItem rows; the
        // attendance records feed it instead.
        if (isset($components[GradeItem::TYPE_ATTENDANCE])
            && ! $components[GradeItem::TYPE_ATTENDANCE]['is_graded']) {
            $attendance = $this->attendancePercent($studentId, $classId);

            $percent = $attendance['rate'];

            if ($attendance['has_records']) {
                $components[GradeItem::TYPE_ATTENDANCE]['percent'] = round($percent, 2);
                $components[GradeItem::TYPE_ATTENDANCE]['is_graded'] = true;
                $components[GradeItem::TYPE_ATTENDANCE]['earned'] = $percent;
                $components[GradeItem::TYPE_ATTENDANCE]['possible'] = 100;

                $final += $percent * ($weights[GradeItem::TYPE_ATTENDANCE] / GradeConfiguration::REQUIRED_TOTAL);
                $weightedTotal += $weights[GradeItem::TYPE_ATTENDANCE];
                $anyGraded = true;
            }
        }

        // Renormalise when some components have no data yet, so a student with
        // only quizzes graded is not punished for a missing exam.
        $final = $weightedTotal > 0 && $weightedTotal < GradeConfiguration::REQUIRED_TOTAL
            ? ($final / ($weightedTotal / GradeConfiguration::REQUIRED_TOTAL))
            : $final;

        $final = round(min($final, 100), 2);

        return [
            'student_id' => $studentId,
            'final_grade' => $final,
            'is_graded' => $anyGraded,
            'letter_grade' => $this->grades->percentageToLetter($final),
            'components' => $components,
            'item_breakdown' => $this->itemBreakdown($studentGrades, $itemsById),
        ];
    }

    /**
     * Legacy path: GradeItem.factor is the absolute weight.
     *
     * @param  \Illuminate\Support\Collection<int, GradeItem>  $itemsById
     * @param  \Illuminate\Support\Collection<int, Grade>  $studentGrades
     * @return array<string, mixed>
     */
    protected function legacySummary($items, $itemsById, $studentGrades, int $classId, int $studentId): array
    {
        $summary = $this->summarise($studentGrades);

        // Still expose a component split so the gradebook can render columns
        // even before a configuration exists.
        $components = [];

        foreach (GradeConfiguration::COMPONENTS as $type => $meta) {
            $group = $studentGrades->filter(fn ($g) => ($g->item?->item_type ?? $itemsById->get($g->grade_item_id)?->item_type) === $type);

            if ($group->isEmpty()) {
                continue;
            }

            $components[$type] = [
                'label' => $meta['label'],
                'percent' => round($this->percentOf($group), 2),
                'weight' => 0.0,
                'earned' => round((float) $group->sum(fn ($g) => (float) ($g->points ?? 0)), 2),
                'possible' => round((float) $group->sum(fn ($g) => (float) ($g->item?->max_points ?? 0)), 2),
                'graded_items' => $group->count(),
                'is_graded' => true,
            ];
        }

        return [
            'student_id' => $studentId,
            'final_grade' => $summary['percent'],
            'is_graded' => $summary['is_graded'],
            'letter_grade' => $summary['letter_grade'],
            'components' => $components,
            'item_breakdown' => $this->itemBreakdown($studentGrades, $itemsById),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Grade>  $grades
     */
    protected function percentOf($grades): float
    {
        $earned = 0.0;
        $possible = 0.0;

        foreach ($grades as $grade) {
            $factor = (float) ($grade->item?->factor ?? 1);
            $earned += (float) ($grade->points ?? 0) * $factor;
            $possible += (float) ($grade->item?->max_points ?? 0) * $factor;
        }

        return $possible > 0 ? ($earned / $possible) * 100 : 0.0;
    }

    /**
     * Same maths as GradeService::summariseGrades() but kept local so this
     * service does not depend on a protected method.
     *
     * @param  \Illuminate\Support\Collection<int, Grade>  $grades
     * @return array<string, mixed>
     */
    protected function summarise($grades): array
    {
        $earned = 0.0;
        $possible = 0.0;

        foreach ($grades as $grade) {
            $factor = (float) ($grade->item?->factor ?? 1);
            $earned += (float) ($grade->points ?? 0) * $factor;
            $possible += (float) ($grade->item?->max_points ?? 0) * $factor;
        }

        $percent = $possible > 0 ? round(($earned / $possible) * 100, 2) : 0.0;

        return [
            'percent' => $percent,
            'letter_grade' => $this->grades->percentageToLetter($percent),
            'is_graded' => $grades->isNotEmpty() && $possible > 0,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Grade>  $studentGrades
     * @param  \Illuminate\Support\Collection<int, GradeItem>  $itemsById
     * @return array<int, array<string, mixed>>
     */
    protected function itemBreakdown($studentGrades, $itemsById): array
    {
        $byItem = [];

        foreach ($studentGrades as $grade) {
            $item = $grade->item ?? $itemsById->get($grade->grade_item_id);

            $byItem[$grade->grade_item_id] = [
                'title' => $item?->title ?? 'Unknown item',
                'item_type' => $item?->item_type ?? GradeItem::TYPE_OTHER,
                'points' => $grade->points === null ? null : (float) $grade->points,
                'max_points' => (float) ($item?->max_points ?? 0),
                'is_graded' => $grade->points !== null,
            ];
        }

        return $byItem;
    }

    /**
     * Attendance as a percentage, counting present and late as attended.
     *
     * @return array{has_records: bool, rate: float, present: int, total: int}
     */
    public function attendancePercent(int $studentId, ?int $classId = null): array
    {
        $query = AttendanceRecord::where('student_id', $studentId);

        if ($classId !== null) {
            $query->where('class_id', $classId);
        }

        $records = $query->get(['status']);

        if ($records->isEmpty()) {
            return ['has_records' => false, 'rate' => 0.0, 'present' => 0, 'total' => 0];
        }

        $attended = $records->whereIn('status', ['present', 'late'])->count();

        return [
            'has_records' => true,
            'rate' => round(($attended / $records->count()) * 100, 2),
            'present' => $attended,
            'total' => $records->count(),
        ];
    }
}