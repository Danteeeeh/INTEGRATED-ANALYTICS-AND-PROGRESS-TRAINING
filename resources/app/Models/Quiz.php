<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
        'allowed_start_time',
        'allowed_end_time',
        'video_url',
        'video_duration_minutes',
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
        'allowed_start_time' => 'datetime',
        'allowed_end_time' => 'datetime',
        'video_duration_minutes' => 'int',
        'status' => 'string',
    ];

    public function available(): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        if ($this->availability_from && Carbon::parse($this->availability_from)->isFuture()) {
            return false;
        }

        if ($this->availability_until && Carbon::parse($this->availability_until)->isPast()) {
            return false;
        }

        // Check time-of-day restrictions
        if ($this->allowed_start_time || $this->allowed_end_time) {
            $now = Carbon::now();
            $currentTime = $now->format('H:i:s');

            if ($this->allowed_start_time && $currentTime < $this->allowed_start_time) {
                return false;
            }

            if ($this->allowed_end_time && $currentTime > $this->allowed_end_time) {
                return false;
            }
        }

        return true;
    }

    public function isLocked(): bool
    {
        // Quiz is locked if it's past the due date (availability_until)
        if ($this->availability_until && Carbon::parse($this->availability_until)->isPast()) {
            return true;
        }

        // Quiz is locked if status is closed
        if ($this->status === self::STATUS_CLOSED) {
            return true;
        }

        return false;
    }

    public function isNotYetAvailable(): bool
    {
        // Quiz is not yet available if availability_from is in the future
        if ($this->availability_from && Carbon::parse($this->availability_from)->isFuture()) {
            return true;
        }

        return false;
    }

    public function getAvailabilityStatus(): string
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return 'not_published';
        }

        if ($this->isLocked()) {
            return 'locked';
        }

        if ($this->isNotYetAvailable()) {
            return 'not_yet_available';
        }

        return 'available';
    }

    public function getAvailabilityMessage(): string
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return 'This quiz is not yet published.';
        }

        if ($this->isLocked()) {
            $dueDate = $this->availability_until ? Carbon::parse($this->availability_until)->format('F j, Y g:i A') : 'Unknown';
            return "This quiz is locked. The due date was {$dueDate}.";
        }

        if ($this->isNotYetAvailable()) {
            $availableFrom = $this->availability_from ? Carbon::parse($this->availability_from)->format('F j, Y g:i A') : 'Unknown';
            return "This quiz will be available starting {$availableFrom}.";
        }

        return 'This quiz is available for taking.';
    }

    public function isOverdue(): bool
    {
        // Quiz is overdue if it's past the due date (availability_until)
        if ($this->availability_until && Carbon::parse($this->availability_until)->isPast()) {
            return true;
        }

        return false;
    }

    public function isOverdueForStudent(int $studentId): bool
    {
        // Check if the quiz has an extension for this student
        $extension = QuizExtension::where('quiz_id', $this->id)
            ->where('student_id', $studentId)
            ->where('extended_until', '>=', now())
            ->first();

        if ($extension) {
            return false;
        }

        return $this->isOverdue();
    }

    public function getEffectiveDeadlineForStudent(int $studentId): ?Carbon
    {
        // Check if the quiz has an extension for this student
        $extension = QuizExtension::where('quiz_id', $this->id)
            ->where('student_id', $studentId)
            ->where('extended_until', '>=', now())
            ->first();

        if ($extension) {
            return Carbon::parse($extension->extended_until);
        }

        return $this->availability_until ? Carbon::parse($this->availability_until) : null;
    }

    public function resolveCourseId(): ?int
    {
        $this->loadMissing(['class', 'module', 'lesson.module']);

        $courseId = $this->class?->course_id
            ?? $this->module?->course_id
            ?? $this->lesson?->module?->course_id;

        return $courseId !== null ? (int) $courseId : null;
    }

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

    /**
     * This assessment's own weighting per question, keyed by question id.
     *
     * The pivot `points` may differ from the bank's default_points, so a frozen
     * question snapshot (§14) must record the assessment's value rather than the
     * bank's — otherwise re-weighting the quiz would rescore old attempts.
     *
     * @return array<int, float|null>
     */
    public function pointsByQuestionId(): array
    {
        return $this->questions()
            ->newPivotQuery()
            ->get(['question_id', 'points'])
            ->pluck('points', 'question_id')
            ->map(fn ($points) => $points === null ? null : (float) $points)
            ->all();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(QuizExtension::class);
    }

    public function learningMaterials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class, 'related_id')
            ->where('related_type', Quiz::class);
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
