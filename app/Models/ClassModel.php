<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'code',
        'course_id',
        'academic_period_id',
        'instructor_id',
        'schedule',
        'room',
        'capacity',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['code'] ?? '');
    }

    public function getMaxStudentsAttribute(): ?int
    {
        return $this->attributes['capacity'] ?? null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ($this->attributes['status'] ?? null) === 'active';
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments', 'class_id', 'student_id')
            ->withPivot('status', 'final_grade', 'notes', 'enrolled_at', 'completed_at')
            ->withTimestamps();
    }

    public function modules(): HasManyThrough
    {
        return $this->hasManyThrough(Module::class, Course::class, 'id', 'course_id')
            ->orderBy('modules.position', 'asc');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(
            Lesson::class,
            Module::class,
            'course_id',   // FK on modules -> courses.id (class's course)
            'module_id',   // FK on lessons -> modules.id
            'course_id',   // local key on classes -> courses.id
            'id'           // local key on modules
        )
            ->orderBy('modules.position', 'asc')
            ->orderBy('lessons.position', 'asc');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'class_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'class_id');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class, 'class_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'class_id');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'class_id');
    }

    public function virtualClasses(): HasMany
    {
        return $this->hasMany(VirtualClass::class, 'class_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'class_id');
    }

    public function gradeCategories(): HasMany
    {
        return $this->hasMany(GradeCategory::class, 'class_id');
    }

    public function gradeItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            GradeItem::class,
            GradeCategory::class,
            'class_id',
            'grade_category_id'
        );
    }

    public function grades(): HasManyThrough
    {
        return $this->hasManyThrough(
            Grade::class,
            GradeItem::class,
            'class_id',     // FK on grade_items -> classes.id
            'grade_item_id' // FK on grades -> grade_items.id
        );
    }

    public function scopeActive($query)
    {
        return $query->where('classes.status', 'active');
    }

    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    public function scopeByAcademicPeriod($query, $periodId)
    {
        return $query->where('academic_period_id', $periodId);
    }

    public function scopeOfCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function getEnrolledCountAttribute()
    {
        return $this->enrollments()->where('enrollments.status', 'active')->count();
    }

    public function getAvailableSeatsAttribute()
    {
        return $this->capacity ? $this->capacity - $this->enrolled_count : null;
    }

    public function isFull()
    {
        return $this->capacity && $this->enrolled_count >= $this->capacity;
    }
}
