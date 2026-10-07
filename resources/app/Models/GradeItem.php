<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeItem extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_ASSIGNMENT = 'assignment';

    public const TYPE_QUIZ = 'quiz';

    public const TYPE_PROJECT = 'project';

    public const TYPE_EXAM = 'exam';

    public const TYPE_PARTICIPATION = 'participation';

    public const TYPE_ATTENDANCE = 'attendance';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'class_id',
        'title',
        'description',
        'max_points',
        'factor',
        'item_type',
        'related_type',
        'related_id',
        'due_date',
        'position',
        'is_released',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'max_points' => 'decimal:2',
            'factor' => 'decimal:2',
            'due_date' => 'datetime',
            'position' => 'integer',
            'is_released' => 'boolean',
            'released_at' => 'timestamp',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function configuration(): HasOne
    {
        return $this->hasOne(GradeConfiguration::class, 'class_id', 'class_id');
    }

    /**
     * Weight of the component this item belongs to, taken from the class's
     * grading configuration. Zero when the component is not weighted.
     */
    public function componentWeight(): float
    {
        $configuration = $this->relationLoaded('configuration')
            ? $this->configuration
            : $this->configuration()->first();

        if (! $configuration) {
            return 0.0;
        }

        return $configuration->weights()[$this->item_type] ?? 0.0;
    }

    public function related(): MorphTo
    {
        return $this->morphTo('related', 'related_type', 'related_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}
