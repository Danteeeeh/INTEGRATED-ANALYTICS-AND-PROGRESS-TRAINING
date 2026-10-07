<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermsOfServiceAcceptance extends Model
{
    use HasFactory;

    protected $fillable = [
        'terms_of_service_id',
        'user_id',
        'accepted_at',
        'ip_address',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function termsOfService(): BelongsTo
    {
        return $this->belongsTo(TermsOfService::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('accepted_at', 'desc');
    }
}
