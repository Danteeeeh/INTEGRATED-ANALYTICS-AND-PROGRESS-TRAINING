<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_thread_id',
        'sender_id',
        'body',
        'attachment_media_id',
        'sent_at',
        'edited_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'timestamp',
            'edited_at' => 'timestamp',
            'deleted_at' => 'timestamp',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'message_thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'attachment_media_id');
    }

    public function readReceipts(): HasMany
    {
        return $this->hasMany(MessageReadReceipt::class, 'message_id');
    }
}
