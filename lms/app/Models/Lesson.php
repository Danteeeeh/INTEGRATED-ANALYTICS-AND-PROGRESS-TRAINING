<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_TEXT = 'text';

    public const TYPE_VIDEO = 'video';

    public const TYPE_AUDIO = 'audio';

    public const TYPE_PDF = 'pdf';

    public const TYPE_DOCUMENT = 'document';

    public const TYPE_PRESENTATION = 'presentation';

    public const TYPE_EXTERNAL = 'external';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'module_id',
        'title',
        'description',
        'objectives',
        'content',
        'duration_minutes',
        'position',
        'lesson_type',
        'external_url',
        'is_required',
        'availability_from',
        'availability_until',
        'completion_rules',
        'status',
        'created_by',
    ];

    protected $casts = [
        'is_required' => 'bool',
        'position' => 'int',
        'duration_minutes' => 'int',
        'availability_from' => 'datetime',
        'availability_until' => 'datetime',
        'completion_rules' => 'array',
        'status' => 'string',
    ];

    /**
     * Legacy alias — `is_published` reads from the real `status` column.
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(MediaFile::class, 'lesson_materials')
            ->withPivot('title', 'description', 'position', 'is_required', 'access_until', 'created_at', 'updated_at')
            ->orderByPivot('position', 'asc');
    }

    public function lessonMaterials(): HasMany
    {
        return $this->hasMany(LessonMaterial::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function scopePublished($query)
    {
        return $query->where('lessons.status', self::STATUS_PUBLISHED);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOfModule($query, $id)
    {
        return $query->where('module_id', $id);
    }
}
