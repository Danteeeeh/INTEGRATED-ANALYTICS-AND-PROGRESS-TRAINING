<?php

namespace App\Services;

use App\Models\CourseProgress;
use App\Models\LearningPlan;
use App\Models\LearningPlanItem;
use App\Models\LessonProgress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LearningPlanService
{
    public function __construct(private StudentPerformanceAssessmentService $performanceAssessment) {}

    /**
     * Generate (or fetch) the active plan for a student from their risk signals.
     *
     * Idempotent: returns the existing active plan if one exists, otherwise
     * creates a new one whose items map 1:1 to the current assessment signals.
     */
    public function generateForStudent(User $student): LearningPlan
    {
        $existing = LearningPlan::query()
            ->where('student_id', $student->id)
            ->where('status', LearningPlan::STATUS_ACTIVE)
            ->with('items')
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $classIds = $student->enrollments()
            ->where('status', 'active')
            ->pluck('class_id');

        $overallProgress = $this->overallProgress($student->id, $classIds);
        $learningStreak = $this->learningStreak($student->id);

        $assessment = $this->performanceAssessment->assess(
            $student->id,
            $classIds,
            $overallProgress,
            $learningStreak,
        );

        $plan = LearningPlan::create([
            'student_id' => $student->id,
            'title' => 'Personalized learning plan',
            'description' => $assessment['summary'],
            'target_date' => Carbon::now()->addDays(14)->toDateString(),
            'status' => LearningPlan::STATUS_ACTIVE,
            'created_by' => $student->id,
        ]);

        $this->syncItemsFromAssessment($plan, $assessment);

        return $plan->fresh('items');
    }

    public function markItem(User $student, LearningPlanItem $item, string $status): LearningPlanItem
    {
        abort_unless($item->plan->student_id === $student->id, 403);

        $item->update([
            'status' => $status,
            'progress_percent' => $status === LearningPlanItem::STATUS_COMPLETED
                ? 100
                : ($status === LearningPlanItem::STATUS_IN_PROGRESS ? 50 : 0),
            'target_date' => $item->target_date ?? Carbon::now()->addDays(7)->toDateString(),
        ]);

        $this->syncPlanStatus($item->plan);

        return $item->fresh();
    }

    protected function syncItemsFromAssessment(LearningPlan $plan, array $assessment): void
    {
        $items = [];

        foreach ($assessment['recommendations'] as $recommendation) {
            $items[] = [
                'title' => $recommendation['title'],
                'description' => $recommendation['detail'],
                'target_date' => Carbon::now()->addDays(7)->toDateString(),
                'status' => LearningPlanItem::STATUS_PENDING,
                'progress_percent' => 0,
            ];
        }

        if ($items === []) {
            $items[] = [
                'title' => 'Complete your first activity',
                'description' => 'Open a lesson, submit an assignment, or complete a quiz to build your learning baseline.',
                'target_date' => Carbon::now()->addDays(7)->toDateString(),
                'status' => LearningPlanItem::STATUS_PENDING,
                'progress_percent' => 0,
            ];
        }

        foreach ($items as $item) {
            $plan->items()->create($item);
        }
    }

    protected function syncPlanStatus(LearningPlan $plan): void
    {
        $items = $plan->items()->get();

        if ($items->isNotEmpty() && $items->every(fn ($i) => $i->status === LearningPlanItem::STATUS_COMPLETED)) {
            $plan->update(['status' => LearningPlan::STATUS_COMPLETED]);
        } elseif ($plan->status === LearningPlan::STATUS_DRAFT) {
            $plan->update(['status' => LearningPlan::STATUS_ACTIVE]);
        }
    }

    protected function overallProgress(int $studentId, Collection $classIds): float
    {
        if ($classIds->isEmpty()) {
            return 0.0;
        }

        $rows = CourseProgress::where('student_id', $studentId)
            ->whereIn('class_id', $classIds)
            ->get(['progress_percent']);

        return $rows->isEmpty() ? 0.0 : (float) $rows->avg('progress_percent');
    }

    protected function learningStreak(int $studentId): int
    {
        // Consecutive days with lesson activity, capped at 30.
        $streak = 0;
        $date = Carbon::today();

        for ($i = 0; $i < 30; $i++) {
            $hasActivity = LessonProgress::where('student_id', $studentId)
                ->whereDate('updated_at', $date->copy()->subDays($i))
                ->exists();

            if ($hasActivity) {
                $streak++;
            } elseif ($i > 0) {
                break;
            }
        }

        return $streak;
    }
}