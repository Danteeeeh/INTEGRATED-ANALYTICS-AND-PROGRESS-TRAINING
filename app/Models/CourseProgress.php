<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseProgress extends Model
{
    use HasFactory;

    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DROPPED = 'dropped';

    protected $fillable = [
        'class_id',
        'student_id',
        'started_at',
        'completed_at',
        'modules_completed',
        'total_modules',
        'lessons_completed',
        'total_lessons',
        'progress_percent',
        'final_grade',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'modules_completed' => 'integer',
            'total_modules' => 'integer',
            'lessons_completed' => 'integer',
            'total_lessons' => 'integer',
            'progress_percent' => 'decimal:2',
            'final_grade' => 'decimal:2',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(CourseCompletion::class, 'progress_id');
    }
}
