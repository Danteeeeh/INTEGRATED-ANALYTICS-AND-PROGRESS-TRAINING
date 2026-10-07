<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('subject');
    }

    public function scopeOfUser($query, $id)
    {
        return $query->where('user_id', $id);
    }

    public function scopeOfAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, $limit = null)
    {
        $q = $query->latest();
        if ($limit) {
            $q->limit($limit);
        }

        return $q;
    }
}
