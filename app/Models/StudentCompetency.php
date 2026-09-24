<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentCompetency extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'competency_id',
        'class_id',
        'current_level',
        'required_level',
        'evidence_count',
        'mastered_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence_count' => 'integer',
            'mastered_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(CompetencyEvidence::class, 'student_competency_id');
    }
}
