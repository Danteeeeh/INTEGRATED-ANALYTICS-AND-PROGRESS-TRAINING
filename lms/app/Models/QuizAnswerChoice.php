<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAnswerChoice extends Model
{
    use HasFactory;

    protected $table = 'quiz_answer_choices';

    protected $fillable = [
        'quiz_answer_id',
        'question_choice_id',
        'is_selected',
    ];

    protected $casts = [
        'is_selected' => 'bool',
    ];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(QuizAnswer::class, 'quiz_answer_id');
    }

    public function choice(): BelongsTo
    {
        return $this->belongsTo(QuestionChoice::class, 'question_choice_id');
    }
}
