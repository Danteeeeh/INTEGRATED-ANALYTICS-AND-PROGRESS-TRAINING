<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamAttempt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_id',
        'student_id',
        'graded_by',
        'attempt_number',
        'started_at',
        'ended_at',
        'submitted_at',
        'time_spent_seconds',
        'time_remaining_seconds',
        'status',
        'score',
        'score_percent',
        'total_points',
        'earned_points',
        'is_passed',
        'proctoring_log',
        'tab_switch_count',
        'suspicious_activity_count',
        'flagged_for_review',
        'proctoring_notes',
        'ip_address',
        'user_agent',
        'browser_fingerprint',
        'confirmed_before_start',
        'confirmed_at',
        'feedback',
        'graded_at',
        'grading_notes',
        'reviewed',
        'reviewed_at',
        'reviewed_by',
        'metadata',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'submitted_at' => 'datetime',
        'time_spent_seconds' => 'integer',
        'time_remaining_seconds' => 'integer',
        'score' => 'decimal:2',
        'score_percent' => 'decimal:2',
        'total_points' => 'decimal:2',
        'earned_points' => 'decimal:2',
        'is_passed' => 'boolean',
        'proctoring_log' => 'array',
        'tab_switch_count' => 'integer',
        'suspicious_activity_count' => 'integer',
        'flagged_for_review' => 'boolean',
        'confirmed_before_start' => 'boolean',
        'confirmed_at' => 'datetime',
        'graded_at' => 'datetime',
        'reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Status constants
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_AUTO_SUBMITTED = 'auto_submitted';
    const STATUS_GRADED = 'graded';
    const STATUS_REVIEWED = 'reviewed';

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class)->where('student_id', $this->student_id);
    }

    public function getGradeAttribute()
    {
        return $this->grade()->first();
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeSubmitted($query)
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_AUTO_SUBMITTED]);
    }

    public function scopeGraded($query)
    {
        return $query->where('status', self::STATUS_GRADED);
    }

    public function scopePassed($query)
    {
        return $query->where('is_passed', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('is_passed', false);
    }

    public function scopeFlagged($query)
    {
        return $query->where('flagged_for_review', true);
    }

    public function scopeOfStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeOfExam($query, $examId)
    {
        return $query->where('exam_id', $examId);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_AUTO_SUBMITTED,
            self::STATUS_GRADED,
            self::STATUS_REVIEWED,
        ]);
    }

    public function isGraded(): bool
    {
        return in_array($this->status, [self::STATUS_GRADED, self::STATUS_REVIEWED]);
    }

    public function hasTimeRemaining(): bool
    {
        if (!$this->exam->duration_minutes) {
            return true;
        }

        if ($this->status !== self::STATUS_IN_PROGRESS) {
            return false;
        }

        $elapsed = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;
        $totalSeconds = $this->exam->duration_minutes * 60;

        return $elapsed < $totalSeconds;
    }

    public function getTimeRemaining(): int
    {
        if (!$this->exam->duration_minutes) {
            return 0;
        }

        $elapsed = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;
        $totalSeconds = $this->exam->duration_minutes * 60;

        return max(0, $totalSeconds - $elapsed);
    }

    public function hasFlaggedActivity(): bool
    {
        return $this->flagged_for_review 
            || $this->tab_switch_count > 0 
            || $this->suspicious_activity_count > 0;
    }

    public function getScoreDisplay(): string
    {
        if ($this->score_percent === null) {
            return 'N/A';
        }

        return number_format($this->score_percent, 1) . '%';
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_NOT_STARTED => 'Not Started',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_AUTO_SUBMITTED => 'Auto Submitted',
            self::STATUS_GRADED => 'Graded',
            self::STATUS_REVIEWED => 'Reviewed',
            default => 'Unknown',
        };
    }
}
