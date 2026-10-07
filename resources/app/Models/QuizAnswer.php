<?php

namespace App\Models;

use App\Services\QuestionSnapshotService;

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
        'question_snapshot',
        'answer_text',
        'points_awarded',
        'is_correct',
        'feedback',
        'graded_by',
        'graded_at',
    ];

    protected $casts = [
        'question_snapshot' => 'array',
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

    /**
     * The frozen copy of the question captured when the attempt began (§14).
     *
     * Null for every attempt recorded before question snapshots existed, in
     * which case callers fall back to the live question.
     *
     * @return array<string, mixed>|null
     */
    public function snapshotArray(): ?array
    {
        return app(QuestionSnapshotService::class)->read($this->question_snapshot);
    }

    /**
     * Question text as this student actually saw it, not as it reads today.
     */
    public function renderedQuestionText(): string
    {
        return app(QuestionSnapshotService::class)->textFor($this->snapshotArray(), $this->question);
    }

    /**
     * Choices as this student actually saw them.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, key: string, text: string, is_correct: bool}>
     */
    public function renderedChoices(): \Illuminate\Support\Collection
    {
        return app(QuestionSnapshotService::class)->choicesFor($this->snapshotArray(), $this->question);
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
