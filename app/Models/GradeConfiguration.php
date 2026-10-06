<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The instructor's grading configuration for one subject offering.
 *
 * A configuration assigns a percentage weight to each grade component. Weights
 * must total exactly 100 for the subject to be considered configured, which is
 * why {@see totalWeight()} is the single source of truth and the UI validates
 * against it rather than against a hard-coded 100.
 */
class GradeConfiguration extends Model
{
    use HasFactory;

    /**
     * Components the instructor can weight, in gradebook column order.
     *
     * Keys map to GradeItem::$fillable 'item_type' values and to the
     * snake_cased weight column.
     *
     * @var array<string, array{label: string, column: string, optional: bool, hint: string}>
     */
    public const COMPONENTS = [
        GradeItem::TYPE_QUIZ => [
            'label' => 'Quizzes',
            'column' => 'quiz_weight',
            'optional' => false,
            'hint' => 'Quizzes, tests and short assessments.',
        ],
        GradeItem::TYPE_ASSIGNMENT => [
            'label' => 'Assignments',
            'column' => 'assignment_weight',
            'optional' => false,
            'hint' => 'Projects, reports and submissions.',
        ],
        GradeItem::TYPE_EXAM => [
            'label' => 'Exams',
            'column' => 'exam_weight',
            'optional' => false,
            'hint' => 'Midterm and final examinations.',
        ],
        GradeItem::TYPE_PROJECT => [
            'label' => 'Projects',
            'column' => 'project_weight',
            'optional' => true,
            'hint' => 'Separate from assignments when a course needs it.',
        ],
        GradeItem::TYPE_PARTICIPATION => [
            'label' => 'Participation',
            'column' => 'participation_weight',
            'optional' => true,
            'hint' => 'Class participation and activity scores.',
        ],
        GradeItem::TYPE_ATTENDANCE => [
            'label' => 'Virtual Class Attendance',
            'column' => 'attendance_weight',
            'optional' => true,
            'hint' => 'Optional. Leave unchecked to grade it manually instead.',
        ],
        GradeItem::TYPE_OTHER => [
            'label' => 'Other',
            'column' => 'other_weight',
            'optional' => true,
            'hint' => 'Anything that does not fit the components above.',
        ],
    ];

    /** Weights must add up to exactly this. */
    public const REQUIRED_TOTAL = 100.0;

    protected $fillable = [
        'class_id',
        'quiz_weight',
        'assignment_weight',
        'exam_weight',
        'project_weight',
        'participation_weight',
        'attendance_weight',
        'other_weight',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'quiz_weight' => 'float',
            'assignment_weight' => 'float',
            'exam_weight' => 'float',
            'project_weight' => 'float',
            'participation_weight' => 'float',
            'attendance_weight' => 'float',
            'other_weight' => 'float',
        ];
    }

    public function class(): BelongsTo
    {
        // The FK must be named explicitly: belongsTo would guess
        // "class_model_id" from the model name.
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Every weight keyed by component type.
     *
     * @return array<string, float>
     */
    public function weights(): array
    {
        $weights = [];

        foreach (self::COMPONENTS as $type => $meta) {
            $weights[$type] = round((float) ($this->{$meta['column']} ?? 0), 2);
        }

        return $weights;
    }

    public function totalWeight(): float
    {
        return round(array_sum($this->weights()), 2);
    }

    /**
     * True when the weights are usable for grading.
     *
     * A configuration that does not total 100 is stored (so the instructor does
     * not lose their work) but is never used by GradeService.
     */
    public function isValid(): bool
    {
        return abs($this->totalWeight() - self::REQUIRED_TOTAL) < 0.001;
    }

    /**
     * Components the instructor actually switched on.
     *
     * @return array<string, float>
     */
    public function enabledWeights(): array
    {
        return array_filter(
            $this->weights(),
            fn ($weight) => $weight > 0
        );
    }

    /**
     * Human-readable reason the configuration is not usable, or null when it is.
     */
    public function validationMessage(): ?string
    {
        $total = $this->totalWeight();

        if ($this->enabledWeights() === []) {
            return 'Select at least one graded component.';
        }

        if ($this->isValid()) {
            return null;
        }

        $difference = round(abs(self::REQUIRED_TOTAL - $total), 2);

        return sprintf(
            'Component weights total %s%%. They must add up to exactly %s%% (%s %s%%).',
            number_format($total, 2),
            number_format(self::REQUIRED_TOTAL, 0),
            $total < self::REQUIRED_TOTAL ? 'add' : 'remove',
            number_format($difference, 2)
        );
    }

    /**
     * Is attendance part of the computed grade?
     */
    public function gradesAttendance(): bool
    {
        return ($this->weights()[GradeItem::TYPE_ATTENDANCE] ?? 0) > 0;
    }
}