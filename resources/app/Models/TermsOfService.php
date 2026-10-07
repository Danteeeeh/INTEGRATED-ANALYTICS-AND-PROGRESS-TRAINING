<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TermsOfService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'version',
        'is_active',
        'effective_date',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_date' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(TermsOfServiceAcceptance::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('is_active', true)
            ->where('effective_date', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->is_active && $this->effective_date && $this->effective_date->lte(now());
    }
}
