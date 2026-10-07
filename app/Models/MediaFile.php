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

    /**
     * Ids already reported as missing this request, so a page listing many
     * broken attachments logs each one once instead of on every check.
     *
     * @var array<int, true>
     */
    protected static array $reportedMissing = [];

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
            $exists = Storage::disk($this->disk)->exists($this->path);
        } catch (\Throwable $e) {
            // A misconfigured or unreachable disk must not 500 the whole page,
            // but it is also not the same as "the file is gone" — record it so
            // the two are distinguishable in the logs.
            \Illuminate\Support\Facades\Log::warning('media_file.disk_unreachable', [
                'media_file_id' => $this->id,
                'disk' => $this->disk,
                'path' => $this->path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $exists && ! isset(self::$reportedMissing[$this->id])) {
            self::$reportedMissing[$this->id] = true;

            // The row survives but the blob does not. Almost always means the
            // filesystem is not persistent (or not shared between instances):
            // the database keeps the metadata while the bytes evaporate.
            \Illuminate\Support\Facades\Log::warning('media_file.bytes_missing', [
                'media_file_id' => $this->id,
                'disk' => $this->disk,
                'path' => $this->path,
                'original_name' => $this->original_name,
                'expected_absolute' => $this->full_path,
            ]);
        }

        return $exists;
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
