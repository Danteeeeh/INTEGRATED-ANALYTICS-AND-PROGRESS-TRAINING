<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningMaterial extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_FILE = 'file';

    const TYPE_LINK = 'link';

    const TYPE_VIDEO = 'video';

    const TYPE_DOCUMENT = 'document';

    const TYPE_PDF = 'pdf';

    const TYPE_IMAGE = 'image';

    const TYPE_AUDIO = 'audio';

    protected $fillable = [
        'title',
        'description',
        'material_type',
        'file_path',
        'file_name',
        'file_size',
        'file_mime_type',
        'url',
        'related_type',
        'related_id',
        'position',
        'is_required',
        'access_until',
        'created_by',
    ];

    protected $casts = [
        'position' => 'int',
        'is_required' => 'bool',
        'access_until' => 'datetime',
        'file_size' => 'int',
    ];

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOfQuiz($query)
    {
        return $query->where('related_type', Quiz::class);
    }

    public function scopeOfExam($query)
    {
        return $query->where('related_type', Exam::class);
    }

    public function scopeOfAssignment($query)
    {
        return $query->where('related_type', Assignment::class);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('material_type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    public function isAccessible(): bool
    {
        if ($this->access_until && now()->gt($this->access_until)) {
            return false;
        }

        return true;
    }

    public function getFileSizeInMB(): float
    {
        return round($this->file_size / 1024 / 1024, 2);
    }

    public function getTypeLabel(): string
    {
        return match($this->material_type) {
            self::TYPE_FILE => 'File',
            self::TYPE_LINK => 'Link',
            self::TYPE_VIDEO => 'Video',
            self::TYPE_DOCUMENT => 'Document',
            self::TYPE_PDF => 'PDF',
            self::TYPE_IMAGE => 'Image',
            self::TYPE_AUDIO => 'Audio',
            default => 'Material',
        };
    }
}
