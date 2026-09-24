<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubricCriterion extends Model
{
    protected $fillable = [
        'rubric_id',
        'criterion',
        'description',
        'position',
        'max_points',
    ];

    protected $casts = [
        'position' => 'integer',
        'max_points' => 'float',
    ];

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(RubricLevel::class)->orderBy('position');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(RubricAssessment::class);
    }
}
