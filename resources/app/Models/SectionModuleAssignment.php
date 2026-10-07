<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionModuleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'module_id',
        'course_id',
        'status',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeActive($query)
    {
        return $query->where('section_module_assignments.status', 'active');
    }

    public function scopeBySection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeByModule($query, $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }
}
