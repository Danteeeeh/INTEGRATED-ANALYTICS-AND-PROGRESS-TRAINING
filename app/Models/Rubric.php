<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rubric extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'title',
        'description',
        'course_id',
        'class_id',
        'created_by',
        'is_shared',
        'status',
    ];

    protected $casts = [
        'is_shared' => 'boolean',
        'status' => 'string',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('position');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'rubric_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(RubricAssessment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('rubrics.status', self::STATUS_ACTIVE);
    }

    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    public function scopeOfCourse($query, $id)
    {
        return $query->where('course_id', $id);
    }
}
