<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'gradable_type',
        'gradable_id',
        'student_id',
        'author_id',
        'body',
        'attachment_media_id',
        'is_private',
        'rating',
        'feedback_type',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'rating' => 'integer',
            'tags' => 'array',
        ];
    }

    public function gradable(): MorphTo
    {
        return $this->morphTo('gradable', 'gradable_type', 'gradable_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'attachment_media_id');
    }
}
