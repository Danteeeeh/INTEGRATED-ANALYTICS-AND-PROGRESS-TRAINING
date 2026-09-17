<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Certificate extends Model
{
    use HasFactory;

    public const STATUS_ISSUED = 'issued';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'class_id',
        'course_id',
        'student_id',
        'issued_by',
        'issued_at',
        'certificate_number',
        'verification_code',
        'template_name',
        'student_name_display',
        'course_name_display',
        'completion_date',
        'final_grade',
        'status',
        'revoked_at',
        'revoke_reason',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'timestamp',
            'completion_date' => 'date',
            'final_grade' => 'decimal:2',
            'revoked_at' => 'timestamp',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(CourseCompletion::class, 'certificate_id');
    }
}
