<?php

namespace App\Models;

use App\Services\QuestionSnapshotService;

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
        'question_snapshot',
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
        'question_snapshot' => 'array',
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

    /**
     * The frozen copy of the question captured when the exam began (§14).
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

    public function choices(): HasMany
    {
        return $this->hasMany(ExamAnswerChoice::class);
    }
}
