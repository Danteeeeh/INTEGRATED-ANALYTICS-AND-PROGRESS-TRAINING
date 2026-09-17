<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizAttempt extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_AUTO_SUBMITTED = 'auto_submitted';

    const STATUS_GRADED = 'graded';

    protected $fillable = [
        'quiz_id',
        'student_id',
        'attempt_number',
        'started_at',
        'ended_at',
        'submitted_at',
        'time_spent_seconds',
        'score',
        'score_percent',
        'is_passed',
        'status',
        'graded_by',
        'graded_at',
        'locked_at',
    ];

    protected $casts = [
        'attempt_number' => 'int',
        'time_spent_seconds' => 'int',
        'score' => 'float',
        'score_percent' => 'float',
        'is_passed' => 'bool',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'locked_at' => 'datetime',
        'status' => 'string',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function scopeInProgress($query)
    {
        return $query->where('quiz_attempts.status', self::STATUS_IN_PROGRESS);
    }

    public function scopeSubmitted($query)
    {
        return $query->whereIn('quiz_attempts.status', [self::STATUS_SUBMITTED, self::STATUS_AUTO_SUBMITTED]);
    }

    public function scopeGraded($query)
    {
        return $query->where('quiz_attempts.status', self::STATUS_GRADED);
    }

    public function scopePassed($query)
    {
        return $query->where('is_passed', true);
    }

    public function scopeOfStudent($query, $id)
    {
        return $query->where('student_id', $id);
    }

    public function scopeOfQuiz($query, $id)
    {
        return $query->where('quiz_id', $id);
    }
}
