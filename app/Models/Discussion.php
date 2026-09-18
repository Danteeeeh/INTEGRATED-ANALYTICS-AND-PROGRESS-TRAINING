<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discussion extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_GENERAL = 'general';

    public const TYPE_ACADEMIC = 'academic';

    public const TYPE_QA = 'qa';

    public const TYPE_GRADED = 'graded';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'course_id',
        'class_id',
        'module_id',
        'lesson_id',
        'title',
        'slug',
        'description',
        'discussion_type',
        'is_pinned',
        'is_locked',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'bool',
            'is_locked' => 'bool',
            'status' => 'string',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
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

    public function posts(): HasMany
    {
        return $this->hasMany(DiscussionPost::class)->orderBy('created_at');
    }

    public function rootPosts(): HasMany
    {
        return $this->hasMany(DiscussionPost::class)->whereNull('parent_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(DiscussionSubscription::class);
    }

    public function scopePublished($query)
    {
        return $query->where('discussions.status', self::STATUS_PUBLISHED);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeOfClass($query, $id)
    {
        return $query->where('class_id', $id);
    }

    public function scopeOfCourse($query, $id)
    {
        return $query->where('course_id', $id);
    }
}
