<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonMaterialProgress extends Model
{
    use HasFactory;

    protected $table = 'lesson_material_progress';

    protected $fillable = [
        'lesson_id',
        'student_id',
        'material_id',
        'accessed_at',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(LessonMaterial::class, 'material_id');
    }
}
