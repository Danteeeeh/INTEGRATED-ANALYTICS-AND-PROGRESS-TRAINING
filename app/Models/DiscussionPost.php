<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscussionPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'discussion_id',
        'parent_id',
        'author_id',
        'body',
        'is_pinned',
        'is_approved',
        'reported_count',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'bool',
            'is_approved' => 'bool',
            'reported_count' => 'int',
        ];
    }

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DiscussionPost::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(DiscussionPost::class, 'parent_id');
    }

    public function attachments(): BelongsToMany
    {
        return $this->belongsToMany(MediaFile::class, 'discussion_post_attachments', 'discussion_post_id', 'media_file_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOfDiscussion($query, $id)
    {
        return $query->where('discussion_id', $id);
    }
}
