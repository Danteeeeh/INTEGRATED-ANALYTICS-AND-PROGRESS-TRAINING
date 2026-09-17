<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuizAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'answer_text',
        'points_awarded',
        'is_correct',
        'feedback',
        'graded_by',
        'graded_at',
    ];

    protected $casts = [
        'points_awarded' => 'float',
        'is_correct' => 'bool',
        'graded_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function selectedChoices(): BelongsToMany
    {
        return $this->belongsToMany(QuestionChoice::class, 'quiz_answer_choices')
            ->withPivot('is_selected')
            ->withTimestamps();
    }
}
