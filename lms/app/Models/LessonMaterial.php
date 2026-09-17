<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonMaterial extends Model
{
    use HasFactory;

    protected $table = 'lesson_materials';

    protected $fillable = [
        'lesson_id',
        'media_file_id',
        'title',
        'description',
        'position',
        'is_required',
        'access_until',
    ];

    protected $casts = [
        'is_required' => 'bool',
        'access_until' => 'datetime',
        'position' => 'int',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }
}
