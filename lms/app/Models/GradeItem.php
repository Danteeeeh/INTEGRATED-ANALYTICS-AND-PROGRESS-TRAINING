<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'grade_category_id',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(GradeCategory::class, 'grade_category_id');
    }

    public function gradeCategory(): BelongsTo
    {
        return $this->category();
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
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
