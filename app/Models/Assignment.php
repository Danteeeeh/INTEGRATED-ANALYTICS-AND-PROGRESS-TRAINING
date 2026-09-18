<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CLOSED = 'closed';

    public const TYPE_TEXT = 'text';

    public const TYPE_FILE = 'file';

    public const TYPE_MULTIPLE_FILES = 'multiple_files';

    protected $fillable = [
        'class_id',
        'module_id',
        'lesson_id',
        'title',
        'slug',
        'instructions',
        'points',
        'submission_type',
        'due_date',
        'allow_late',
        'late_submission_deduction_percent',
        'max_attempts',
        'allow_resubmission',
        'resubmission_deadline',
        'availability_from',
        'availability_until',
        'rubric_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'points' => 'integer',
        'allow_late' => 'boolean',
        'late_submission_deduction_percent' => 'integer',
        'max_attempts' => 'integer',
        'allow_resubmission' => 'boolean',
        'due_date' => 'datetime',
        'resubmission_deadline' => 'datetime',
        'availability_from' => 'datetime',
        'availability_until' => 'datetime',
        'status' => 'string',
    ];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AssignmentAttachment::class)->orderBy('position');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function scopePublished($query)
    {
        return $query->where('assignments.status', self::STATUS_PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where('assignments.status', self::STATUS_DRAFT);
    }

    public function scopeClosed($query)
    {
        return $query->where('assignments.status', self::STATUS_CLOSED);
    }

    public function scopeOfClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeDueSoon($query, $days = 7)
    {
        return $query->whereBetween('due_date', [Carbon::now(), Carbon::now()->addDays($days)]);
    }

    public function scopeActive($query)
    {
        return $query->where('assignments.status', self::STATUS_PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('availability_from')
                    ->orWhere('availability_from', '<=', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('availability_until')
                    ->orWhere('availability_until', '>=', Carbon::now());
            });
    }
}
