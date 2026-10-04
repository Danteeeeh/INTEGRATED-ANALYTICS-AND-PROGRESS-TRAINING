<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'answer_text',
        'answer_data',
        'is_correct',
        'points_awarded',
        'feedback',
        'grader_notes',
        'answered_at',
        'time_spent_seconds',
        'flagged_for_review',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'points_awarded' => 'decimal:2',
        'answered_at' => 'datetime',
        'time_spent_seconds' => 'integer',
        'flagged_for_review' => 'boolean',
        'answer_data' => 'array',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(ExamAnswerChoice::class);
    }
}
