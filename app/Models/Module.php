<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'objectives',
        'position',
        'is_required',
        'prerequisites',
        'completion_requirements',
        'external_video_url',
        'video_thumbnail_url',
        'status',
        'created_by',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Legacy alias — `is_published` reads from the real `status` column.
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position', 'asc');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ModuleAttachment::class)->orderBy('position');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class, 'module_id');
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(StudentModuleAssignment::class);
    }

    public function scopePublished($query)
    {
        return $query->where('modules.status', self::STATUS_PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where('modules.status', self::STATUS_DRAFT);
    }

    public function scopeOfCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Get YouTube video ID from URL
     */
    public function getYouTubeVideoId(): ?string
    {
        if (!$this->external_video_url) {
            return null;
        }

        $url = $this->external_video_url;
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
        
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get YouTube thumbnail URL
     */
    public function getYouTubeThumbnailUrl(): ?string
    {
        if ($this->video_thumbnail_url) {
            return $this->video_thumbnail_url;
        }

        $videoId = $this->getYouTubeVideoId();
        if ($videoId) {
            return "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
        }

        return null;
    }

    /**
     * Check if the video is from YouTube
     */
    public function isYouTubeVideo(): bool
    {
        return $this->getYouTubeVideoId() !== null;
    }

    /**
     * Get embedded video URL
     */
    public function getEmbeddedVideoUrl(): ?string
    {
        if (!$this->external_video_url) {
            return null;
        }

        if ($this->isYouTubeVideo()) {
            $videoId = $this->getYouTubeVideoId();
            return "https://www.youtube.com/embed/{$videoId}";
        }

        // For other video platforms, return the original URL
        return $this->external_video_url;
    }
}
