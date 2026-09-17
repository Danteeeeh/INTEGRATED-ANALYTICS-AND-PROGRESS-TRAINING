<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReadReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'recipient_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'timestamp',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
