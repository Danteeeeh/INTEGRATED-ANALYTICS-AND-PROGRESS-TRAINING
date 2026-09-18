<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_item_id',
        'student_id',
        'points',
        'score_percent',
        'letter_grade',
        'override_note',
        'is_override',
        'graded_by',
        'graded_at',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'score_percent' => 'decimal:2',
            'is_override' => 'boolean',
            'graded_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(GradeItem::class, 'grade_item_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(GradeHistory::class, 'grade_id');
    }
}
