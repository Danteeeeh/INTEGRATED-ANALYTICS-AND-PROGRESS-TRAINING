<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'instructions',
        'exam_type',
        'class_id',
        'course_id',
        'module_id',
        'created_by',
        'duration_minutes',
        'total_points',
        'passing_score_percent',
        'grade_weight',
        'attempt_limit',
        'allow_review',
        'requires_proctoring',
        'proctoring_method',
        'proctoring_instructions',
        'record_session',
        'detect_tab_switch',
        'detect_copy_paste',
        'allow_navigation',
        'shuffle_questions',
        'shuffle_choices',
        'show_question_number',
        'show_timer',
        'auto_save_seconds',
        'auto_submit_on_timeout',
        'result_visibility',
        'show_correct_answers',
        'show_score',
        'results_release_date',
        'starts_at',
        'ends_at',
        'allowed_start_time',
        'allowed_end_time',
        'video_url',
        'video_duration_minutes',
        'require_confirmation',
        'status',
        'slug',
        'settings',
        'published_at',
        'closed_at',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'total_points' => 'decimal:2',
        'passing_score_percent' => 'decimal:2',
        'grade_weight' => 'integer',
        'attempt_limit' => 'integer',
        'allow_review' => 'boolean',
        'requires_proctoring' => 'boolean',
        'record_session' => 'boolean',
        'detect_tab_switch' => 'boolean',
        'detect_copy_paste' => 'boolean',
        'allow_navigation' => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_choices' => 'boolean',
        'show_question_number' => 'boolean',
        'show_timer' => 'boolean',
        'auto_save_seconds' => 'integer',
        'auto_submit_on_timeout' => 'boolean',
        'show_correct_answers' => 'boolean',
        'show_score' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'allowed_start_time' => 'datetime',
        'allowed_end_time' => 'datetime',
        'video_duration_minutes' => 'integer',
        'results_release_date' => 'datetime',
        'require_confirmation' => 'boolean',
        'settings' => 'array',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_CLOSED = 'closed';
    const STATUS_ARCHIVED = 'archived';

    // Exam type constants — the three semestral assessments.
    const TYPE_PRELIM = 'prelim';

    const TYPE_MIDTERM = 'midterm';

    const TYPE_FINALS = 'finals';

    /**
     * Legacy values kept readable so exams saved before the Type field existed
     * still render. Never offered on a form; relabel with lms:relabel-exams.
     */
    const TYPE_MODULE = 'module';

    const TYPE_OTHER = 'other';

    /**
     * The choices an instructor may pick, in semester order.
     *
     * Drives every create/edit form and the list filters so the three places
     * cannot drift apart.
     *
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_PRELIM => 'Prelim',
            self::TYPE_MIDTERM => 'Midterm',
            self::TYPE_FINALS => 'Finals',
        ];
    }

    /**
     * Types accepted from a request.
     *
     * Includes the legacy values so editing an exam created before Type
     * existed does not fail validation on its own unchanged field.
     *
     * @return array<int, string>
     */
    public static function acceptedTypes(): array
    {
        return array_merge(array_keys(self::typeOptions()), [self::TYPE_MODULE, self::TYPE_OTHER]);
    }

    // Result visibility constants
    const VISIBILITY_IMMEDIATELY = 'immediately';
    const VISIBILITY_AFTER_GRADING = 'after_grading';
    const VISIBILITY_AFTER_ALL_SUBMISSIONS = 'after_all_submissions';
    const VISIBILITY_AFTER_DATE = 'after_date';
    const VISIBILITY_NEVER = 'never';

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot('order', 'points', 'is_required', 'pool_size', 'random_from_pool')
            ->orderBy('pivot_order');
    }

    /**
     * This exam's own weighting per question, keyed by question id.
     *
     * See Quiz::pointsByQuestionId() — the pivot value must be what a frozen
     * snapshot (§14) records, not the bank's default.
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
        return $this->hasMany(ExamAttempt::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(ExamExtension::class);
    }

    public function learningMaterials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class, 'related_id')
            ->where('related_type', Exam::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeAvailable($query)
    {
        $now = now();
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $now);
            });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('exam_type', $type);
    }

    public function scopePrelim($query)
    {
        return $query->where('exam_type', self::TYPE_PRELIM);
    }

    public function scopeMidterm($query)
    {
        return $query->where('exam_type', self::TYPE_MIDTERM);
    }

    public function scopeFinals($query)
    {
        return $query->where('exam_type', self::TYPE_FINALS);
    }

    public function scopeModule($query)
    {
        return $query->where('exam_type', self::TYPE_MODULE);
    }

    public function isAvailable(): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        // Check time-of-day restrictions
        if ($this->allowed_start_time || $this->allowed_end_time) {
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

    public function hasStarted(): bool
    {
        return $this->starts_at ? now()->gte($this->starts_at) : true;
    }

    public function hasEnded(): bool
    {
        return $this->ends_at ? now()->gt($this->ends_at) : false;
    }

    public function isProctored(): bool
    {
        return $this->requires_proctoring;
    }

    public function getQuestionCount(): int
    {
        return $this->questions()->count();
    }

    public function getTotalPoints(): float
    {
        return $this->questions()->sum('points') ?: $this->total_points;
    }

    public function getTypeLabel(): string
    {
        return match ($this->exam_type) {
            self::TYPE_PRELIM => 'Prelim Exam',
            self::TYPE_MIDTERM => 'Midterm Exam',
            self::TYPE_FINALS => 'Finals Exam',
            self::TYPE_MODULE => 'Module Exam',
            self::TYPE_OTHER => 'Other',
            default => 'Exam',
        };
    }

    public function isOverdue(): bool
    {
        // Exam is overdue if it's past the end date
        if ($this->ends_at && now()->gt($this->ends_at)) {
            return true;
        }

        return false;
    }

    public function isOverdueForStudent(int $studentId): bool
    {
        // Check if the exam has an extension for this student
        $extension = ExamExtension::where('exam_id', $this->id)
            ->where('student_id', $studentId)
            ->where('extended_until', '>=', now())
            ->first();

        if ($extension) {
            return false;
        }

        return $this->isOverdue();
    }

    public function getEffectiveDeadlineForStudent(int $studentId): ?\Illuminate\Support\Carbon
    {
        // Check if the exam has an extension for this student
        $extension = ExamExtension::where('exam_id', $this->id)
            ->where('student_id', $studentId)
            ->where('extended_until', '>=', now())
            ->first();

        if ($extension) {
            return \Illuminate\Support\Carbon::parse($extension->extended_until);
        }

        return $this->ends_at ? \Illuminate\Support\Carbon::parse($this->ends_at) : null;
    }
}
