<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'question_id',
        'order',
        'points',
        'is_required',
        'pool_size',
        'random_from_pool',
    ];

    protected $casts = [
        'order' => 'integer',
        'points' => 'decimal:2',
        'is_required' => 'boolean',
        'pool_size' => 'integer',
        'random_from_pool' => 'boolean',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
