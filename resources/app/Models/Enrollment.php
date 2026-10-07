<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DROPPED = 'dropped';

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
        return $query->where('enrollments.status', self::STATUS_ACTIVE);
    }

    public function scopeCompleted($query)
    {
        return $query->where('enrollments.status', self::STATUS_COMPLETED);
    }

    public function scopeDropped($query)
    {
        return $query->where('enrollments.status', self::STATUS_DROPPED);
    }

    /**
     * Enrollments a student is still expected to complete.
     *
     * Dropped enrollments must never count toward a rate or an average — a
     * student who left the class cannot submit, so leaving them in the
     * denominator drags every metric down forever.
     */
    public function scopeCountable($query)
    {
        return $query->whereIn('enrollments.status', [
            self::STATUS_PENDING,
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
        ]);
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
        $this->update(['status' => self::STATUS_DROPPED]);
    }

    /**
     * Enroll a student, reviving a previously dropped row when one exists.
     *
     * enrollments is UNIQUE(student_id, class_id), so a student who drops out
     * of a class and is later re-enrolled cannot get a second row — a plain
     * create() would blow up with a raw SQL integrity error. The dropped row is
     * reused instead, which is also what the surrounding checks already assume
     * when they only look for a non-dropped enrollment.
     *
     * @param  string  $status  Status to apply to a revived row.
     * @return array{0: self|null, 1: bool} [enrollment, revived]
     */
    public static function enroll(int $studentId, int $classId, string $status = self::STATUS_ACTIVE, ?string $notes = null): array
    {
        $existing = static::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->first();

        if ($existing && $existing->status !== self::STATUS_DROPPED) {
            return [null, false];
        }

        if ($existing) {
            $existing->update([
                'status' => $status,
                'enrolled_at' => now(),
                'completed_at' => null,
                'final_grade' => null,
                'notes' => $notes,
            ]);

            return [$existing, true];
        }

        $enrollment = static::create([
            'student_id' => $studentId,
            'class_id' => $classId,
            'status' => $status,
            'enrolled_at' => now(),
            'notes' => $notes,
        ]);

        return [$enrollment, false];
    }
}
