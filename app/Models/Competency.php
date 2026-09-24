<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'framework_id',
        'parent_id',
        'code',
        'name',
        'description',
        'learning_outcomes',
        'mastery_levels',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'learning_outcomes' => 'array',
            'mastery_levels' => 'array',
            'position' => 'integer',
        ];
    }

    public function framework(): BelongsTo
    {
        return $this->belongsTo(CompetencyFramework::class, 'framework_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function courseMappings(): HasMany
    {
        return $this->hasMany(CourseCompetency::class);
    }

    public function studentRecords(): HasMany
    {
        return $this->hasMany(StudentCompetency::class);
    }
}
