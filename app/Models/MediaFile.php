<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'disk',
        'path',
        'original_name',
        'file_name',
        'mime_type',
        'size',
        'extension',
        'metadata',
        'uploader_id',
        'width',
        'height',
        'thumbnail_url',
        'duration',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size' => 'int',
    ];

    protected static function booted(): void
    {
        static::forceDeleted(function (MediaFile $mediaFile) {
            Storage::disk($mediaFile->disk)->delete($mediaFile->path);
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_materials');
    }

    public function assignmentAttachments(): HasMany
    {
        return $this->hasMany(AssignmentAttachment::class);
    }

    public function submissionFiles(): HasMany
    {
        return $this->hasMany(SubmissionFile::class);
    }

    public function getUrlAttribute(): string
    {
        if (in_array($this->disk, ['public', 's3'])) {
            return Storage::disk($this->disk)->url($this->path);
        }

        return route('media.download', ['id' => $this->id]);
    }

    public function getFullPathAttribute(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }
}
