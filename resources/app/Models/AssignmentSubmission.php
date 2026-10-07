<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignmentSubmission extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_GRADED = 'graded';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_RESUBMITTED = 'resubmitted';

    protected $fillable = [
        'assignment_id',
        'student_id',
        'attempt_number',
        'submission_text',
        'submitted_at',
        'is_late',
        'status',
        'graded_by',
        'graded_at',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'is_late' => 'boolean',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'status' => 'string',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class, 'assignment_submission_id');
    }

    public function allStudentGrades(): HasMany
    {
        return $this->hasMany(Grade::class, 'student_id', 'student_id');
    }

    public function getGradeAttribute()
    {
        if ($this->relationLoaded('allStudentGrades')) {
            return $this->allStudentGrades->first(function ($grade) {
                return $grade->item
                    && $grade->item->related_type === Assignment::class
                    && $grade->item->related_id === $this->assignment_id;
            });
        }

        return Grade::where('student_id', $this->student_id)
            ->whereHas('item', function ($query) {
                $query->where('related_type', Assignment::class)
                    ->where('related_id', $this->assignment_id);
            })
            ->first();
    }

    public function rubricAssessments(): HasMany
    {
        return $this->hasMany(RubricAssessment::class, 'assignment_submission_id');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('assignment_submissions.status', self::STATUS_SUBMITTED);
    }

    public function scopeGraded($query)
    {
        return $query->where('assignment_submissions.status', self::STATUS_GRADED);
    }

    public function scopeLate($query)
    {
        return $query->where('is_late', true);
    }

    public function scopeOfStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeOfAssignment($query, $id)
    {
        return $query->where('assignment_id', $id);
    }
}
