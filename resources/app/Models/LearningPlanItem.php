<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPlanItem extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'learning_plan_id',
        'competency_id',
        'course_id',
        'class_id',
        'title',
        'description',
        'target_date',
        'progress_percent',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'progress_percent' => 'decimal:2',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(LearningPlan::class, 'learning_plan_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}
