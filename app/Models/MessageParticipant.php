<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_thread_id',
        'user_id',
        'last_read_at',
        'is_deleted',
        'is_muted',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'timestamp',
            'is_deleted' => 'boolean',
            'is_muted' => 'boolean',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'message_thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
