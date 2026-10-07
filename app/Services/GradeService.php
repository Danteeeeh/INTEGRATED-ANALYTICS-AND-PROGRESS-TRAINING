<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeHistory;
use App\Models\GradeItem;
use Illuminate\Support\Facades\Cache;
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
                'resource_type' => \App\Models\Grade::class,
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
                'resource_type' => \App\Models\Grade::class,
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
                'resource_type' => \App\Models\Grade::class,
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
                'resource_type' => \App\Models\ClassModel::class,
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
                'resource_type' => \App\Models\Course::class,
                'resource_id' => $course->id,
                'new_values' => ['course_id' => $course->id],
            ]);
        });
    }

    /**
     * Single source of truth for a student's grade in a class.
     *
     * Method: points-weighted across grade items that are BOTH released and
     * graded. Ungraded or unreleased items are excluded from the denominator
     * so a student is never penalised for work that has not been scored or
     * published yet.
     */
    public function computeStudentClassGrade(int $studentId, int $classId): array
    {
        $grades = Grade::query()
            ->where('student_id', $studentId)
            ->whereHas('item', fn ($q) => $q->where('class_id', $classId)->where('is_released', true))
            ->with('item:id,max_points,factor')
            ->get();

        return $this->summariseGrades($grades);
    }

    /**
     * Batch version of {@see computeStudentClassGrade()} for a whole class.
     *
     * @param  array<int>|null  $studentIds  Restrict to these students (null = everyone graded).
     * @return array<int, array{earned_points: float, max_points: float, percent: float, letter_grade: string, graded_items: int, is_graded: bool}>
     */
    public function computeClassGradeSummaries(
        int $classId,
        ?array $studentIds = null,
        ?\Illuminate\Support\Carbon $gradedSince = null
    ): array {
        $query = Grade::query()
            ->whereHas('item', fn ($q) => $q->where('class_id', $classId)->where('is_released', true))
            ->with('item:id,max_points,factor');

        if ($studentIds !== null) {
            $query->whereIn('student_id', $studentIds);
        }

        // Optional reporting window. Left null by every existing caller, so
        // this only narrows results when a caller explicitly asks for it.
        //
        // A grade with no graded_at was recorded without a grading timestamp,
        // which is common for manually entered marks. Excluding it would make
        // a period-filtered report silently drop real grades, so fall back to
        // when the row was created.
        if ($gradedSince !== null) {
            $query->where(function ($q) use ($gradedSince) {
                $q->where('graded_at', '>=', $gradedSince)
                    ->orWhere(function ($q2) use ($gradedSince) {
                        $q2->whereNull('graded_at')
                            ->where('created_at', '>=', $gradedSince);
                    });
            });
        }

        $summaries = [];

        foreach ($query->get()->groupBy('student_id') as $studentId => $grades) {
            $summaries[$studentId] = $this->summariseGrades($grades);
        }

        return $summaries;
    }

    /**
     * Computed grades for an arbitrary set of enrollments (which may span
     * several classes), batched to avoid one query per row.
     *
     * A student can hold several of the passed enrollments at once, so each
     * student's grades are merged across *all* of their classes by summing
     * earned and possible points. Using array union here would silently keep
     * only the first class and drop the rest.
     *
     * @param  iterable<int, Enrollment>  $enrollments
     * @return array<int, array<string, mixed>>  Keyed by student id.
     */
    public function computeSummariesForEnrollments(iterable $enrollments): array
    {
        $byClass = [];

        foreach ($enrollments as $enrollment) {
            $byClass[$enrollment->class_id][] = $enrollment->student_id;
        }

        $perClass = [];

        foreach ($byClass as $classId => $studentIds) {
            $perClass[$classId] = $this->computeClassGradeSummaries(
                $classId,
                array_values(array_unique($studentIds))
            );
        }

        // Merge every class a student appears in.
        $merged = [];

        foreach ($perClass as $classSummaries) {
            foreach ($classSummaries as $studentId => $summary) {
                if (! isset($merged[$studentId])) {
                    $merged[$studentId] = $summary;

                    continue;
                }

                $earned = $merged[$studentId]['earned_points'] + $summary['earned_points'];
                $possible = $merged[$studentId]['max_points'] + $summary['max_points'];

                $percent = $possible > 0 ? round(($earned / $possible) * 100, 2) : 0.0;

                $merged[$studentId] = [
                    'earned_points' => round($earned, 2),
                    'max_points' => round($possible, 2),
                    'percent' => $percent,
                    'letter_grade' => $this->percentageToLetter($percent),
                    'graded_items' => $merged[$studentId]['graded_items'] + $summary['graded_items'],
                    'is_graded' => $merged[$studentId]['is_graded'] || $summary['is_graded'],
                ];
            }
        }

        return $merged;
    }

    /**
     * Per-class summaries for a set of enrollments.
     *
     * Unlike {@see computeSummariesForEnrollments()} nothing is merged across
     * classes, so a caller that needs one line per subject — a transcript, for
     * example — gets a distinct grade for each.
     *
     * @param  iterable<int, Enrollment>  $enrollments
     * @return array<int, array<int, array<string, mixed>>>  [classId => [studentId => summary]]
     */
    public function computeSummariesByClass(iterable $enrollments): array
    {
        $byClass = [];

        foreach ($enrollments as $enrollment) {
            $byClass[$enrollment->class_id][] = $enrollment->student_id;
        }

        $perClass = [];

        foreach ($byClass as $classId => $studentIds) {
            $perClass[$classId] = $this->computeClassGradeSummaries(
                $classId,
                array_values(array_unique($studentIds))
            );
        }

        return $perClass;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Grade>  $grades
     */
    /**
     * Summary of whatever work this student has been graded on.
 *
     * Deliberately NOT gated on completeness: this backs operational counts
     * ("how many students have graded work", class averages, grade
     * distribution, at-risk lists), where a partially graded student still has
     * to be counted. Whether a student may be *published* a final term grade is
     * decided by GradeBreakdownService, which is the single place that rule
     * lives.
     */
protected function summariseGrades($grades): array
    {
        $earned = 0.0;
        $possible = 0.0;

        foreach ($grades as $grade) {
            // Grade items carry a weighting (factor): exams often 0.3/0.3/0.4,
            // participation 0.1, etc. Weighting is applied here so the instructor
            // gradebook, Performance Analytics and the student gradebook all agree.
            $factor = (float) ($grade->item?->factor ?? 1);
            $earned += (float) $grade->points * $factor;
            $possible += (float) ($grade->item?->max_points ?? 0) * $factor;
        }

        $percent = $possible > 0 ? round(($earned / $possible) * 100, 2) : 0.0;

        return [
            'earned_points' => round($earned, 2),
            'max_points' => round($possible, 2),
            'percent' => $percent,
            'letter_grade' => $this->percentageToLetter($percent),
            'graded_items' => $grades->count(),
            'is_graded' => $grades->isNotEmpty() && $possible > 0,
        ];
    }

    public function calculateFinalGrade(Enrollment $enrollment): array
    {
        return $this->computeStudentClassGrade($enrollment->student_id, $enrollment->class_id);
    }

    protected function calculatePercentage(float $points, float $maxPoints): float
    {
        return $maxPoints > 0 ? ($points / $maxPoints) * 100 : 0;
    }

    public function percentageToLetter(float $percentage): string
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

    /**
     * Drop the cached instructor dashboard so Performance Analytics reflects a
     * grade change immediately instead of after the cache TTL.
     */
    public function invalidateInstructorDashboardCache(?int $classId = null): void
    {
        $instructorIds = $classId
            ? ClassModel::where('id', $classId)->pluck('instructor_id')
            : ClassModel::distinct()->pluck('instructor_id');

        foreach ($instructorIds->filter()->unique() as $instructorId) {
            Cache::forget("instructor_dashboard_{$instructorId}");
        }
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

        return [
            'grades' => $grades,
            'overall_average' => $this->computeStudentClassGrade($studentId, $classId)['percent'],
        ];
    }

    public function getClassGradebook(int $classId): array
    {
        $class = ClassModel::with(['enrollments.student', 'course'])->find($classId);

        $gradebook = [];

        foreach ($class->enrollments as $enrollment) {
            $studentGrades = $this->getStudentGradesForClass($enrollment->student_id, $classId);
            $finalGrade = $this->calculateFinalGrade($enrollment);

            $gradebook[] = [
                'student' => $enrollment->student,
                'enrollment' => $enrollment,
                'grades' => $studentGrades['grades'],
                'overall_average' => $studentGrades['overall_average'],
                'final_grade' => $finalGrade,
            ];
        }

        return [
            'class' => $class,
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
                'resource_type' => \App\Models\Grade::class,
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
