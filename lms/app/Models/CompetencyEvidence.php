<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CompetencyEvidence extends Model
{
    use HasFactory;

    public const TYPE_GRADE = 'grade';

    public const TYPE_SUBMISSION = 'submission';

    public const TYPE_QUIZ_ATTEMPT = 'quiz_attempt';

    public const TYPE_ACTIVITY = 'activity';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'student_competency_id',
        'evidence_type',
        'evidence_ref_type',
        'evidence_ref_id',
        'notes',
        'recorded_by',
        'recorded_at',
        'attachment_media_id',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'timestamp',
        ];
    }

    public function studentCompetency(): BelongsTo
    {
        return $this->belongsTo(StudentCompetency::class, 'student_competency_id');
    }

    public function evidence(): MorphTo
    {
        return $this->morphTo('evidence', 'evidence_ref_type', 'evidence_ref_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'attachment_media_id');
    }
}
