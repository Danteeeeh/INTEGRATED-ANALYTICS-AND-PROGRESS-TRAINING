<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubricLevel extends Model
{
    protected $fillable = [
        'rubric_criterion_id',
        'name',
        'description',
        'points',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
        'points' => 'float',
    ];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'rubric_criterion_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(RubricAssessment::class);
    }
}
