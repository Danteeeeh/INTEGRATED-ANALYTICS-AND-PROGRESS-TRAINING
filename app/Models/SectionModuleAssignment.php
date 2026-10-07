<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Every column below is qualified on purpose. `modules` also carries
 * `course_id` and `status`, so any caller that joins this model against
 * `modules` (to order by position) would hit "Column 'status' is ambiguous"
 * on MySQL/MariaDB the moment a bare column name is used.
 */
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
        return $query->where($this->qualifyColumn('status'), 'active');
    }

    public function scopeBySection($query, $sectionId)
    {
        return $query->where($this->qualifyColumn('section_id'), $sectionId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where($this->qualifyColumn('course_id'), $courseId);
    }

    public function scopeByModule($query, $moduleId)
    {
        return $query->where($this->qualifyColumn('module_id'), $moduleId);
    }
}
