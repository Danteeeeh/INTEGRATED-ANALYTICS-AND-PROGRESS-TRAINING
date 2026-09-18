<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT = 'draft';

    const STATUS_PUBLISHED = 'published';

    const STATUS_CLOSED = 'closed';

    const VISIBILITY_ALWAYS = 'always';

    const VISIBILITY_AFTER_GRADING = 'after_grading';

    const VISIBILITY_NEVER = 'never';

    protected $fillable = [
        'class_id',
        'module_id',
        'lesson_id',
        'title',
        'slug',
        'description',
        'instructions',
        'time_limit_minutes',
        'attempt_limit',
        'passing_score_percent',
        'shuffle_questions',
        'shuffle_choices',
        'allow_navigation',
        'auto_save_seconds',
        'auto_submit_on_timeout',
        'result_visibility',
        'review_allowed',
        'show_correct_answers',
        'availability_from',
        'availability_until',
        'status',
        'created_by',
    ];

    protected $casts = [
        'time_limit_minutes' => 'int',
        'attempt_limit' => 'int',
        'passing_score_percent' => 'int',
        'shuffle_questions' => 'bool',
        'shuffle_choices' => 'bool',
        'allow_navigation' => 'bool',
        'auto_save_seconds' => 'int',
        'auto_submit_on_timeout' => 'bool',
        'review_allowed' => 'bool',
        'show_correct_answers' => 'bool',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'quiz_questions')
            ->withPivot('position', 'points', 'is_required', 'pool_size')
            ->orderBy('quiz_questions.position')
            ->withTimestamps();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function scopePublished($query)
    {
        return $query->where('quizzes.status', self::STATUS_PUBLISHED);
    }

    public function scopeClosed($query)
    {
        return $query->where('quizzes.status', self::STATUS_CLOSED);
    }

    public function scopeOfClass($query, $id)
    {
        return $query->where('class_id', $id);
    }

    public function scopeAvailable($query)
    {
        return $query->where('quizzes.status', self::STATUS_PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('availability_from')
                    ->orWhere('availability_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('availability_until')
                    ->orWhere('availability_until', '>=', now());
            });
    }
}
