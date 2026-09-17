<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    const TYPE_MULTIPLE_ANSWER = 'multiple_answer';

    const TYPE_TRUE_FALSE = 'true_false';

    const TYPE_IDENTIFICATION = 'identification';

    const TYPE_SHORT_ANSWER = 'short_answer';

    const TYPE_ESSAY = 'essay';

    const DIFFICULTY_EASY = 'easy';

    const DIFFICULTY_MEDIUM = 'medium';

    const DIFFICULTY_HARD = 'hard';

    protected $fillable = [
        'question_bank_id',
        'question_type',
        'question_text',
        'explanation',
        'difficulty',
        'default_points',
        'tags',
        'created_by',
        'status',
    ];

    protected $casts = [
        'tags' => 'array',
        'default_points' => 'float',
        'status' => 'string',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(QuestionChoice::class)->orderBy('position');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_questions')
            ->withPivot('position', 'points', 'is_required', 'pool_size')
            ->withTimestamps();
    }

    public function quizAnswers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('questions.status', 'active');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('question_type', $type);
    }

    public function scopeDifficulty($query, $level)
    {
        return $query->where('difficulty', $level);
    }

    public function scopeOfBank($query, $bankId)
    {
        return $query->where('question_bank_id', $bankId);
    }
}
