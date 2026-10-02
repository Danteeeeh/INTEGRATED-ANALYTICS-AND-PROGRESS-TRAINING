<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\LessonMaterialProgress;
use App\Models\LessonProgress;
use Carbon\Carbon;

/**
 * Central authority for lesson completion rules.
 *
 * Lessons may carry a `completion_rules` JSON payload, e.g.:
 *   [
 *     'require_all_materials' => true,
 *     'min_minutes'           => 10,
 *     'require_content_view'  => true,
 *   ]
 *
 * The service normalizes those rules, builds a human-readable checklist for
 * the student page, and decides whether a student is eligible to mark the
 * lesson complete. The student controller MUST re-check eligibility on the
 * POST /complete request — never trusts the button alone.
 */
class LessonCompletionService
{
    /**
     * Normalized rules for a lesson. Every rule the UI cares about is always
     * present as a key, so views do not need null-coalescing gymnastics.
     */
    public function rules(Lesson $lesson): array
    {
        $raw = is_array($lesson->completion_rules) ? $lesson->completion_rules : [];

        $minMinutes = isset($raw['min_minutes']) ? (int) $raw['min_minutes'] : 0;

        return [
            'require_all_materials' => (bool) ($raw['require_all_materials'] ?? false),
            'min_minutes' => max(0, $minMinutes),
            'require_content_view' => (bool) ($raw['require_content_view'] ?? false),
        ];
    }

    /**
     * Whether the lesson has any active completion rules at all.
     */
    public function hasRules(Lesson $lesson): bool
    {
        $rules = $this->rules($lesson);

        return $rules['require_all_materials']
            || $rules['min_minutes'] > 0
            || $rules['require_content_view'];
    }

    /**
     * The human checklist. Each item: ['key', 'label', 'met', 'detail'].
     *
     * @param  array<int>  $accessedMaterialIds  material ids the student opened
     */
    public function checklist(Lesson $lesson, LessonProgress $progress, array $accessedMaterialIds = []): array
    {
        $rules = $this->rules($lesson);
        $items = [];

        // Content viewed: met once the lesson page has been opened (we treat
        // having a progress row + last_accessed_at as "viewed").
        if ($rules['require_content_view']) {
            $viewed = $progress->last_accessed_at !== null;
            $items[] = [
                'key' => 'require_content_view',
                'label' => 'Open the lesson content',
                'met' => $viewed,
                'detail' => $viewed ? 'Lesson content opened.' : 'Open this lesson to meet this rule.',
            ];
        }

        // All materials viewed.
        if ($rules['require_all_materials']) {
            $materialRowIds = LessonMaterial::where('lesson_id', $lesson->id)->pluck('id')->all();
            $total = count($materialRowIds);
            $viewed = count(array_intersect($accessedMaterialIds, $materialRowIds));
            $items[] = [
                'key' => 'require_all_materials',
                'label' => 'Review all lesson materials',
                'met' => $total > 0 && $viewed >= $total,
                'detail' => $total > 0
                    ? "{$viewed} of {$total} materials opened."
                    : 'This lesson has no materials yet.',
            ];
        }

        // Minimum time spent.
        if ($rules['min_minutes'] > 0) {
            $seconds = (int) $progress->total_seconds;
            $target = $rules['min_minutes'] * 60;
            $items[] = [
                'key' => 'min_minutes',
                'label' => "Spend at least {$rules['min_minutes']} minute(s) on this lesson",
                'met' => $seconds >= $target,
                'detail' => $seconds >= $target
                    ? 'Time requirement met.'
                    : sprintf('Spent %d of %d minute(s).', (int) floor($seconds / 60), $rules['min_minutes']),
            ];
        }

        return $items;
    }

    /**
     * Whether the student is currently eligible to mark the lesson complete.
     */
    public function isEligible(Lesson $lesson, LessonProgress $progress, array $accessedMaterialIds = []): bool
    {
        foreach ($this->checklist($lesson, $progress, $accessedMaterialIds) as $item) {
            if (! $item['met']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Accumulate time-on-lesson. Called on every page view: the delta between
     * the previous last_accessed_at and now (clamped to sane bounds) is added
     * to total_seconds.
     */
    public function recordVisit(LessonProgress $progress, ?Carbon $now = null): void
    {
        $now = $now ?? now();

        if ($progress->status === LessonProgress::STATUS_COMPLETED) {
            // Once complete we stop accumulating; keep last_accessed fresh only.
            $progress->forceFill(['last_accessed_at' => $now])->save();

            return;
        }

        $delta = 0;
        if ($progress->last_accessed_at !== null) {
            $elapsed = $progress->last_accessed_at->diffInSeconds($now);
            // Clamp: ignore sub-second ticks and anything over 2 hours between
            // visits (tab left open overnight must not count as study time).
            $delta = max(0, min($elapsed, 7200));
        }

        $total = (int) $progress->total_seconds + $delta;

        $progress->forceFill([
            'total_seconds' => max(0, $total),
            'last_accessed_at' => $now,
        ])->save();
    }

    /**
     * Mark a material as accessed (idempotent). $materialId is the
     * lesson_materials row id (LessonMaterial::id).
     */
    public function markMaterialAccessed(Lesson $lesson, int $materialId, int $studentId): bool
    {
        $material = LessonMaterial::where('lesson_id', $lesson->id)->find($materialId);

        if (! $material) {
            return false;
        }

        LessonMaterialProgress::firstOrCreate(
            ['material_id' => $materialId, 'student_id' => $studentId],
            [
                'lesson_id' => $lesson->id,
                'accessed_at' => now(),
            ]
        );

        return true;
    }

    /**
     * Material ids the student has accessed within this lesson.
     *
     * @return array<int>
     */
    public function accessedMaterialIds(Lesson $lesson, int $studentId): array
    {
        return LessonMaterialProgress::where('lesson_id', $lesson->id)
            ->where('student_id', $studentId)
            ->pluck('material_id')
            ->all();
    }
}
