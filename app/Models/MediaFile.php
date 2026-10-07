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

    public function moduleAttachments(): HasMany
    {
        return $this->hasMany(ModuleAttachment::class);
    }

    public function submissionFiles(): HasMany
    {
        return $this->hasMany(SubmissionFile::class);
    }

    /**
     * Download/serve link for this file.
     *
     * `null` when the row is soft-deleted: the files.download route binds
     * {mediaFile} implicitly, and implicit binding never matches a trashed
     * row, so rendering a link for one hands the user a bare 404. Callers
     * should show "file unavailable" instead.
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->trashed()) {
            return null;
        }

        if (in_array($this->disk, ['public', 's3'])) {
            return Storage::disk($this->disk)->url($this->path);
        }

        return route('files.download', ['mediaFile' => $this->id]);
    }

    /**
     * Whether the bytes are actually still on the disk this row points at.
     *
     * The DB row and the stored blob are separate things: a row can outlive
     * its file (restored backup, manual cleanup, a container that was rebuilt).
     * The download route 404s when the blob is gone, so views check this first
     * to say so plainly instead of offering a link that dead-ends.
     */
    public function fileExists(): bool
    {
        if ($this->trashed() || ! $this->path || ! $this->disk) {
            return false;
        }

        try {
            return Storage::disk($this->disk)->exists($this->path);
        } catch (\Throwable) {
            // Misconfigured/unreachable disk — treat as missing rather than
            // letting a storage exception bubble up as a 500.
            return false;
        }
    }

    public function getFullPathAttribute(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    /**
     * Compatibility accessors: several controllers and the file service still
     * reference `file_path` / `file_type`, which were never columns. Map them
     * onto the real `path` / `mime_type` columns so those call sites work.
     */
    public function getFilePathAttribute(): string
    {
        return (string) $this->path;
    }

    public function getFileTypeAttribute(): ?string
    {
        return $this->mime_type;
    }

    public function getFileSizeAttribute(): ?int
    {
        return $this->size;
    }

    public function getUploadedByAttribute(): ?int
    {
        return $this->uploader_id;
    }

    public function getIsPublicAttribute(): bool
    {
        return false; // Default to private, adjust if public/private logic is added later
    }
}
