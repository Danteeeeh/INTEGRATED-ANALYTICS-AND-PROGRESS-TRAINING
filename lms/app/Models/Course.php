<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'description',
        'objectives',
        'syllabus',
        'prerequisites',
        'duration_weeks',
        'credits',
        'academic_period_id',
        'category_id',
        'thumbnail',
        'status',
        'created_by',
    ];

    protected $casts = [
        'duration_weeks' => 'integer',
        'credits' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('position', 'asc');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Module::class)->orderBy('modules.position', 'asc')->orderBy('lessons.position', 'asc');
    }

    public function assignments(): HasManyThrough
    {
        // Assignments are class-scoped (class_id); a course's assignments come through its classes.
        return $this->hasManyThrough(Assignment::class, ClassModel::class, 'course_id', 'class_id');
    }

    public function quizzes(): HasManyThrough
    {
        return $this->hasManyThrough(Quiz::class, ClassModel::class, 'course_id', 'class_id');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /**
     * Whether the given user manages this course — as its creator OR an
     * instructor assigned to teach a class under it.
     */
    public function isManagedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($this->created_by === $user->id) {
            return true;
        }

        return $user->classesInstructing()->where('course_id', $this->id)->exists();
    }

    public function virtualClasses(): HasManyThrough
    {
        return $this->hasManyThrough(VirtualClass::class, ClassModel::class, 'course_id', 'class_id');
    }

    public function competencies()
    {
        return $this->belongsToMany(Competency::class, 'course_competencies')
            ->withPivot('is_required', 'weight', 'notes')
            ->withTimestamps();
    }

    public function badges(): HasMany
    {
        return $this->hasMany(Badge::class);
    }

    public function gradeCategories(): HasMany
    {
        return $this->hasMany(GradeCategory::class);
    }

    public function enrollments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Enrollment::class,
            ClassModel::class,
            'course_id',
            'class_id'
        );
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['title'] ?? '');
    }

    public function scopePublished($query)
    {
        return $query->where('courses.status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('courses.status', 'draft');
    }

    public function scopeArchived($query)
    {
        return $query->where('courses.status', 'archived');
    }

    public function scopeOfCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeOfAcademicPeriod($query, $periodId)
    {
        return $query->where('academic_period_id', $periodId);
    }
}
