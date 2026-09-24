<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BadgeAward extends Model
{
    use HasFactory;

    protected $fillable = [
        'badge_id',
        'student_id',
        'issued_by',
        'issued_at',
        'award_reason',
        'evidence_ref',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'timestamp',
        ];
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
