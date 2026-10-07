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

    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_ARCHIVED = 'archived';

    /** Question types that can be auto-graded. Everything else needs a human. */
    const OBJECTIVE_TYPES = [
        self::TYPE_MULTIPLE_CHOICE,
        self::TYPE_MULTIPLE_ANSWER,
        self::TYPE_TRUE_FALSE,
        self::TYPE_IDENTIFICATION,
    ];

    protected $fillable = [
        'question_bank_id',
        'category_id',
        'question_type',
        'question_text',
        'explanation',
        'difficulty',
        'default_points',
        'tags',
        'created_by',
        'status',
        'is_case_sensitive',
    ];

    protected $casts = [
        'tags' => 'array',
        'default_points' => 'float',
        'status' => 'string',
        'is_case_sensitive' => 'boolean',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
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

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_questions')
            ->withPivot('order', 'points', 'is_required', 'pool_size', 'random_from_pool');
    }

    /**
     * Every answer ever recorded against this question in a quiz.
     *
     * The foreign key must be named explicitly: Eloquent would otherwise guess
     * `quiz_answer_id`, and there is no such column.
     */
    public function quizAnswers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'question_id');
    }

    public function examAnswers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'question_id');
    }

    public function scopeActive($query)
    {
        return $query->where('questions.status', 'active');
    }

    /**
     * Inactive questions are still selectable in the Test Bank but never offered
     * to students for new attempts.
     */
    public function scopeVisible($query)
    {
        return $query->whereIn('questions.status', [self::STATUS_ACTIVE, self::STATUS_DRAFT]);
    }

    /**
     * Questions a student may be served for a *new* attempt.
     *
     * Inactive (archived) questions stay in the bank for reuse but are never
     * offered to students again (§3, §13). Draft and active questions behave as
     * they always have — filtering drafts out too would silently blank existing
     * quizzes whose instructors never changed the default.
     *
     * Historic attempts are unaffected either way: they read their snapshot.
     */
    public function scopeAvailableForAttempts($query)
    {
        return $query->where('questions.status', '!=', self::STATUS_ARCHIVED);
    }

    public function scopeOfCategory($query, int $categoryId)
    {
        return $query->where('questions.category_id', $categoryId);
    }

    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('questions.created_by', $userId);
    }

    public function isObjective(): bool
    {
        return in_array($this->question_type, self::OBJECTIVE_TYPES, true);
    }

    /**
     * Is this question still referenced by a live quiz or exam?
     *
     * Deleting a used question would cascade away the pivot rows and leave past
     * attempts pointing at nothing, so callers must archive instead (§5).
     */
    public function isInUse(): bool
    {
        if (\Illuminate\Support\Facades\DB::table('quiz_questions')->where('question_id', $this->id)->exists()) {
            return true;
        }

        return \Illuminate\Support\Facades\DB::table('exam_questions')->where('question_id', $this->id)->exists();
    }

    /** Label for the spec's "Moderate" wording — the stored value is "medium". */
    public function difficultyLabel(): string
    {
        return match ($this->difficulty) {
            self::DIFFICULTY_EASY => 'Easy',
            self::DIFFICULTY_HARD => 'Difficult',
            default => 'Moderate',
        };
    }

    public function typeLabel(): string
    {
        return match ($this->question_type) {
            self::TYPE_MULTIPLE_CHOICE => 'Multiple Choice',
            self::TYPE_MULTIPLE_ANSWER => 'Multiple Answer',
            self::TYPE_TRUE_FALSE => 'True / False',
            self::TYPE_IDENTIFICATION => 'Identification',
            self::TYPE_SHORT_ANSWER => 'Short Answer',
            self::TYPE_ESSAY => 'Essay',
            default => ucfirst(str_replace('_', ' ', (string) $this->question_type)),
        };
    }

    /**
     * "Inactive" in the spec is stored as "archived" in the existing enum.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DRAFT => 'Draft',
            default => 'Inactive',
        };
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
