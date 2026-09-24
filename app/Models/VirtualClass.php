<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualClass extends Model
{
    use HasFactory, SoftDeletes;

    public const PROVIDER_ZOOM = 'zoom';

    public const PROVIDER_GOOGLE_MEET = 'google_meet';

    public const PROVIDER_MICROSOFT_TEAMS = 'microsoft_teams';

    public const PROVIDER_OTHER = 'other';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'course_id',
        'class_id',
        'instructor_id',
        'title',
        'description',
        'meeting_date',
        'start_time',
        'end_time',
        'meeting_provider',
        'meeting_url',
        'meeting_id',
        'meeting_password',
        'recurrence',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'recurrence' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(VirtualClassAttendee::class);
    }
}
