<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeHistory extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const CREATED_AT = null;

    /**
     * The table associated with the model.
     * Explicit: Laravel's pluralizer would guess `grade_histories`,
     * but the real table (and its migration) is `grade_history`.
     */
    protected $table = 'grade_history';

    protected $fillable = [
        'grade_id',
        'grade_item_id',
        'student_id',
        'previous_points',
        'new_points',
        'previous_percent',
        'new_percent',
        'previous_letter',
        'new_letter',
        'changed_by',
        'change_reason',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_points' => 'decimal:2',
            'new_points' => 'decimal:2',
            'previous_percent' => 'decimal:2',
            'new_percent' => 'decimal:2',
            'changed_at' => 'timestamp',
        ];
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(GradeItem::class, 'grade_item_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
