<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAnswerChoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_answer_id',
        'question_choice_id',
    ];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(ExamAnswer::class, 'exam_answer_id');
    }

    public function choice(): BelongsTo
    {
        return $this->belongsTo(QuestionChoice::class, 'question_choice_id');
    }
}
