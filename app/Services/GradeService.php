<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeCategory;
use App\Models\GradeHistory;
use App\Models\GradeItem;
use Illuminate\Support\Facades\DB;

/**
 * GradeService
 *
 * NOTE: Aligned with the current grade_items-based schema (grades belong to
 * grade_items; grade_items carry max_points / is_released and optionally morph
 * to an assignment/quiz via related_type/related_id).
 *
 * Previously this service was written against an abandoned polymorphic
 * grade design (grades.gradable_type / gradable_id / percentage / max_points /
 * comments / is_released / released_at / override_reason) — none of which
 * exist in the schema. All write/read paths now use the real columns.
 */
class GradeService
{
    public function createGrade(array $data): Grade
    {
        return DB::transaction(function () use ($data) {
            $gradeItem = GradeItem::findOrFail($data['grade_item_id']);

            $points = (float) ($data['points'] ?? 0);
            $scorePercent = $gradeItem->max_points > 0
                ? round(($points / $gradeItem->max_points) * 100, 2)
                : 0;

            $grade = Grade::create([
                'grade_item_id' => $gradeItem->id,
                'student_id' => $data['student_id'],
                'points' => $points,
                'score_percent' => $scorePercent,
                'letter_grade' => $data['letter_grade'] ?? $this->percentageToLetter($scorePercent),
                'graded_by' => auth()->id(),
                'graded_at' => now(),
                'feedback' => $data['feedback'] ?? null,
                'is_override' => $data['is_override'] ?? false,
                'override_note' => $data['override_note'] ?? null,
            ]);

            // Record grade history
            GradeHistory::create([
                'grade_id' => $grade->id,
                'grade_item_id' => $gradeItem->id,
                'student_id' => $grade->student_id,
                'previous_points' => null,
                'new_points' => $points,
                'previous_percent' => null,
                'new_percent' => $scorePercent,
                'previous_letter' => null,
                'new_letter' => $grade->letter_grade,
                'changed_by' => auth()->id(),
                'change_reason' => 'Initial grade created',
                'changed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'resource_type' => 'grade',
                'resource_id' => $grade->id,
                'new_values' => [
                    'student_id' => $grade->student_id,
                    'score_percent' => $grade->score_percent,
                ],
            ]);

            return $grade;
        });
    }

    public function updateGrade(Grade $grade, array $data): Grade
    {
        return DB::transaction(function () use ($grade, $data) {
            $oldPercent = $grade->score_percent;

            $points = (float) ($data['points'] ?? $grade->points);
            $scorePercent = $grade->item->max_points > 0
                ? round(($points / $grade->item->max_points) * 100, 2)
                : 0;

            $grade->update([
                'points' => $points,
                'score_percent' => $scorePercent,
                'letter_grade' => $data['letter_grade'] ?? $this->percentageToLetter($scorePercent),
                'feedback' => $data['feedback'] ?? $grade->feedback,
                'is_override' => $data['is_override'] ?? $grade->is_override,
                'override_note' => $data['override_note'] ?? $grade->override_note,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);

            // Record grade history if percentage changed
            if ((float) $oldPercent !== $scorePercent) {
                GradeHistory::create([
                    'grade_id' => $grade->id,
                    'grade_item_id' => $grade->grade_item_id,
                    'student_id' => $grade->student_id,
                    'previous_points' => $data['points'] ?? $grade->points,
                    'new_points' => $points,
                    'previous_percent' => $oldPercent,
                    'new_percent' => $scorePercent,
                    'previous_letter' => $grade->letter_grade,
                    'new_letter' => $grade->letter_grade,
                    'changed_by' => auth()->id(),
                    'change_reason' => $data['reason'] ?? 'Grade updated',
                    'changed_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'resource_type' => 'grade',
                'resource_id' => $grade->id,
                'new_values' => [
                    'previous_score_percent' => $oldPercent,
                    'new_score_percent' => $scorePercent,
                ],
            ]);

            return $grade->fresh();
        });
    }

    public function overrideGrade(Grade $grade, float $newPercentage, string $reason): Grade
    {
        return DB::transaction(function () use ($grade, $newPercentage, $reason) {
            $oldPercent = $grade->score_percent;

            $grade->update([
                'score_percent' => $newPercentage,
                'points' => $grade->item->max_points > 0
                    ? round(($newPercentage / 100) * $grade->item->max_points, 2)
                    : 0,
                'letter_grade' => $this->percentageToLetter($newPercentage),
                'is_override' => true,
                'override_note' => $reason,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);

            // Record grade history
            GradeHistory::create([
                'grade_id' => $grade->id,
                'grade_item_id' => $grade->grade_item_id,
                'student_id' => $grade->student_id,
                'previous_points' => $grade->points,
                'new_points' => $grade->points,
                'previous_percent' => $oldPercent,
                'new_percent' => $newPercentage,
                'previous_letter' => $grade->letter_grade,
                'new_letter' => $grade->letter_grade,
                'changed_by' => auth()->id(),
                'change_reason' => "Grade override: {$reason}",
                'changed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'override',
                'resource_type' => 'grade',
                'resource_id' => $grade->id,
                'new_values' => [
                    'previous_score_percent' => $oldPercent,
                    'new_score_percent' => $newPercentage,
                    'reason' => $reason,
                ],
            ]);

            return $grade->fresh();
        });
    }

    public function releaseGradesForClass(ClassModel $class): void
    {
        DB::transaction(function () use ($class) {
            GradeItem::where('class_id', $class->id)
                ->where('is_released', false)
                ->update([
                    'is_released' => true,
                    'released_at' => now(),
                ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'release_grades',
                'resource_type' => 'class',
                'resource_id' => $class->id,
                'new_values' => ['class_id' => $class->id],
            ]);
        });
    }

    public function releaseGradesForCourse(Course $course): void
    {
        DB::transaction(function () use ($course) {
            GradeItem::whereHas('class', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
                ->where('is_released', false)
                ->update([
                    'is_released' => true,
                    'released_at' => now(),
                ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'release_grades',
                'resource_type' => 'course',
                'resource_id' => $course->id,
                'new_values' => ['course_id' => $course->id],
            ]);
        });
    }

    public function calculateFinalGrade(Enrollment $enrollment): array
    {
        $class = $enrollment->class;
        $gradeCategories = GradeCategory::where('class_id', $class->id)->get();

        if ($gradeCategories->isEmpty()) {
            // Simple average if no categories configured
            $grades = Grade::where('student_id', $enrollment->student_id)
                ->whereHas('item', function ($query) use ($class) {
                    $query->where('class_id', $class->id)
                        ->where('is_released', true);
                })
                ->get();

            $average = $grades->isNotEmpty() ? $grades->avg('score_percent') : 0;
            $letterGrade = $this->percentageToLetter($average);

            return [
                'percentage' => round((float) $average, 2),
                'letter_grade' => $letterGrade,
                'total_points' => $grades->sum('points'),
                'max_points' => $grades->sum(fn ($grade) => $grade->item?->max_points ?? 0),
                'method' => 'simple_average',
            ];
        }

        // Weighted calculation
        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($gradeCategories as $category) {
            $grades = Grade::where('student_id', $enrollment->student_id)
                ->whereHas('item', function ($query) use ($class, $category) {
                    $query->where('class_id', $class->id)
                        ->where('grade_category_id', $category->id)
                        ->where('is_released', true);
                })
                ->get();

            if ($grades->isNotEmpty()) {
                $categoryAverage = $grades->avg('score_percent');
                $weightedSum += $categoryAverage * $category->weight;
                $totalWeight += $category->weight;
            }
        }

        $finalPercentage = $totalWeight > 0 ? ($weightedSum / $totalWeight) : 0;
        $letterGrade = $this->percentageToLetter($finalPercentage);

        return [
            'percentage' => round((float) $finalPercentage, 2),
            'letter_grade' => $letterGrade,
            'weighted_sum' => round($weightedSum, 2),
            'total_weight' => $totalWeight,
            'method' => 'weighted',
        ];
    }

    protected function calculatePercentage(float $points, float $maxPoints): float
    {
        return $maxPoints > 0 ? ($points / $maxPoints) * 100 : 0;
    }

    protected function percentageToLetter(float $percentage): string
    {
        if ($percentage >= 90) {
            return 'A';
        }
        if ($percentage >= 80) {
            return 'B';
        }
        if ($percentage >= 70) {
            return 'C';
        }
        if ($percentage >= 60) {
            return 'D';
        }

        return 'F';
    }

    public function getStudentGradesForClass(int $studentId, int $classId): array
    {
        $grades = Grade::where('student_id', $studentId)
            ->whereHas('item', function ($query) use ($classId) {
                $query->where('class_id', $classId);
            })
            ->with('item.related')
            ->get()
            ->groupBy('grade_item_id');

        $gradeCategories = GradeCategory::where('class_id', $classId)
            ->with('gradeItems')
            ->get();

        return [
            'grades' => $grades,
            'categories' => $gradeCategories,
            'overall_average' => Grade::where('student_id', $studentId)
                ->whereHas('item', function ($query) use ($classId) {
                    $query->where('class_id', $classId)
                        ->where('is_released', true);
                })
                ->avg('score_percent') ?? 0,
        ];
    }

    public function getClassGradebook(int $classId): array
    {
        $class = ClassModel::with(['enrollments.student', 'course'])->find($classId);
        $gradeCategories = GradeCategory::where('class_id', $classId)
            ->with('gradeItems')
            ->get();

        $gradebook = [];

        foreach ($class->enrollments as $enrollment) {
            $studentGrades = $this->getStudentGradesForClass($enrollment->student_id, $classId);
            $finalGrade = $this->calculateFinalGrade($enrollment);

            $gradebook[] = [
                'student' => $enrollment->student,
                'enrollment' => $enrollment,
                'grades' => $studentGrades['grades'],
                'categories' => $studentGrades['categories'],
                'overall_average' => $studentGrades['overall_average'],
                'final_grade' => $finalGrade,
            ];
        }

        return [
            'class' => $class,
            'categories' => $gradeCategories,
            'gradebook' => $gradebook,
        ];
    }

    public function deleteGrade(Grade $grade): bool
    {
        return DB::transaction(function () use ($grade) {
            $oldPercent = $grade->score_percent;

            $grade->delete();

            // Record grade history
            GradeHistory::create([
                'grade_id' => null,
                'grade_item_id' => $grade->grade_item_id,
                'student_id' => $grade->student_id,
                'previous_points' => $grade->points,
                'new_points' => null,
                'previous_percent' => $oldPercent,
                'new_percent' => null,
                'previous_letter' => $grade->letter_grade,
                'new_letter' => null,
                'changed_by' => auth()->id(),
                'change_reason' => 'Grade deleted',
                'changed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'resource_type' => 'grade',
                'resource_id' => $grade->id,
                'new_values' => [
                    'student_id' => $grade->student_id,
                    'previous_score_percent' => $oldPercent,
                ],
            ]);

            return true;
        });
    }
}
