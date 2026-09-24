<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;

    public const EVENT_TYPE_ASSIGNMENT = 'assignment';

    public const EVENT_TYPE_QUIZ = 'quiz';

    public const EVENT_TYPE_VIRTUAL_CLASS = 'virtual_class';

    public const EVENT_TYPE_EXAM = 'exam';

    public const EVENT_TYPE_ANNOUNCEMENT = 'announcement';

    public const EVENT_TYPE_COURSE = 'course';

    public const EVENT_TYPE_PERSONAL = 'personal';

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_COURSE = 'course';

    public const VISIBILITY_CLASS = 'class';

    public const VISIBILITY_PUBLIC = 'public';

    protected $fillable = [
        'user_id',
        'course_id',
        'class_id',
        'title',
        'description',
        'event_type',
        'related_type',
        'related_id',
        'start_at',
        'end_at',
        'is_all_day',
        'location',
        'recurrence',
        'visibility',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_all_day' => 'boolean',
            'recurrence' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'related_type', 'related_id');
    }
}
