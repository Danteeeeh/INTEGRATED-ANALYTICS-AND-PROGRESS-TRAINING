<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One instructor grade-extraction batch for a class.
 *
 * Never an official academic record: AA is the source of truth for final
 * grades. This row only records that LMS assessment scores were extracted and
 * submitted, together with the snapshot the instructor validated — so the
 * export never silently rewrites an official grade.
 */
class GradeSubmission extends Model
{
    use HasFactory;

    public const STATUS_PREVIEWED = 'previewed';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'class_id',
        'instructor_id',
        'submitted_by',
        'status',
        'period_label',
        'student_count',
        'graded_count',
        'missing_count',
        'average_percent',
        'payload',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'average_percent' => 'decimal:2',
        'student_count' => 'integer',
        'graded_count' => 'integer',
        'missing_count' => 'integer',
        'submitted_at' => 'datetime',
    ];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeIncomplete($query)
    {
        return $query->where('missing_count', '>', 0);
    }
}
