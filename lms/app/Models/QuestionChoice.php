<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuestionChoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'choice_text',
        'points',
        'is_correct',
        'position',
        'feedback',
    ];

    protected $casts = [
        'is_correct' => 'bool',
        'points' => 'float',
        'position' => 'int',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function quizAnswerSelections(): BelongsToMany
    {
        return $this->belongsToMany(QuizAnswer::class, 'quiz_answer_choices')
            ->withPivot('is_selected')
            ->withTimestamps();
    }
}
