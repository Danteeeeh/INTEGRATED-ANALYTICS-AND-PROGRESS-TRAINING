<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A topic inside a subject, e.g. "Computer Hardware" under Introduction to
 * Computer Science.
 *
 * Scoped to a course (or global when null) so two subjects can each have a
 * "Networking" topic without colliding — which is what the Test Bank quota
 * builder in §9 needs to select "Hardware = 10, Networking = 15".
 */
class QuestionCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'course_id',
        'created_by',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'category_id');
    }

    /**
     * Categories a given course may use: its own plus any global ones.
     */
    public function scopeForCourse($query, ?int $courseId)
    {
        return $query->where(function ($q) use ($courseId) {
            $q->whereNull('course_id');

            if ($courseId !== null) {
                $q->orWhere('course_id', $courseId);
            }
        });
    }

    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }
}
