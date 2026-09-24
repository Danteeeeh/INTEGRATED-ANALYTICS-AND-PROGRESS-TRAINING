<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'student_id',
        'progress_id',
        'completed_at',
        'completion_percent',
        'final_grade',
        'requirements_met',
        'certificate_id',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'completion_percent' => 'decimal:2',
            'final_grade' => 'decimal:2',
            'requirements_met' => 'array',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function progress(): BelongsTo
    {
        return $this->belongsTo(CourseProgress::class, 'progress_id');
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }
}
