<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'status',
        'final_grade',
        'notes',
        'enrolled_at',
        'completed_at',
    ];

    protected $casts = [
        'final_grade' => 'decimal:2',
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function scopeActive($query)
    {
        return $query->where('enrollments.status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('enrollments.status', 'completed');
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function markAsCompleted($grade = null)
    {
        $this->update([
            'status' => 'completed',
            'final_grade' => $grade,
            'completed_at' => now(),
        ]);
    }

    public function drop()
    {
        $this->update(['status' => 'dropped']);
    }
}
