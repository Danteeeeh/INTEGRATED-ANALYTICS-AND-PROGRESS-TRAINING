<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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

    /**
     * Full start datetime, combining the `meeting_date` + `start_time` columns.
     */
    public function startsAt(): ?Carbon
    {
        if (! $this->meeting_date || ! $this->start_time) {
            return null;
        }

        return Carbon::parse($this->meeting_date->format('Y-m-d').' '.$this->start_time);
    }

    /**
     * Full end datetime, combining the `meeting_date` + `end_time` columns.
     */
    public function endsAt(): ?Carbon
    {
        if (! $this->meeting_date || ! $this->end_time) {
            return null;
        }

        return Carbon::parse($this->meeting_date->format('Y-m-d').' '.$this->end_time);
    }

    /**
     * Human-readable span of the session.
     *
     * Lives here rather than in the view because a session can legitimately
     * end before it starts (a data-entry slip, or a session that crossed
     * midnight): Carbon's diff() hands back a negative interval and formatting
     * that throws, which used to blow up the whole admin page.
     */
    public function durationLabel(): string
    {
        $start = $this->startsAt();
        $end = $this->endsAt();

        if (! $start || ! $end) {
            return 'n/a';
        }

        $minutes = (int) abs($start->diffInMinutes($end, false));

        return sprintf('%d hr %02d min', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Human name for the meeting platform.
     *
     * Lives here rather than in a `@php` block inside the view. Blade compiles
     * `@php ... @endphp` by matching the first `@php` anywhere in the file to
     * the next `@endphp`, so an inline `@php(...)` earlier in the same view
     * swallowed the whole body and the page died on an undefined variable.
     * Keeping it a method also means the student and admin pages cannot disagree
     * about what to call the same provider.
     */
    public function providerLabel(): string
    {
        return match ($this->meeting_provider) {
            'zoom' => 'Zoom',
            'google_meet' => 'Google Meet',
            'microsoft_teams' => 'Microsoft Teams',
            'other' => 'Other',
            null, '' => 'Other',
            default => ucfirst((string) $this->meeting_provider),
        };
    }

    /**
     * Has the scheduled end time already passed?
     */
    public function hasEnded(): bool
    {
        return ($end = $this->endsAt()) !== null && $end->isPast();
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_ONGOING;
    }

    public function canStart(): bool
    {
        return $this->status === self::STATUS_SCHEDULED && ! $this->hasEnded();
    }

    public function canEnd(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_ONGOING], true);
    }

    /**
     * Auto-close the meeting once its end time passes.
     *
     * Called whenever a virtual class is read so the status self-corrects
     * without requiring a scheduler. Cancelled and completed rows are left
     * untouched.
     *
     * @return bool  True when this call changed the status.
     */
    public function syncStatus(): bool
    {
        if (! in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_ONGOING], true)) {
            return false;
        }

        if (! $this->hasEnded()) {
            return false;
        }

        $this->forceFill(['status' => self::STATUS_COMPLETED])->save();

        return true;
    }

    /**
     * Auto-close every meeting whose end time has passed.
     *
     * Meant for `schedule:run` via the console kernel.
     */
    public static function autoCloseExpired(): int
    {
        $affected = 0;

        VirtualClass::query()
            ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_ONGOING])
            ->whereDate('meeting_date', '<=', now()->toDateString())
            ->get()
            ->each(function (self $virtualClass) use (&$affected) {
                if ($virtualClass->syncStatus()) {
                    $affected++;
                }
            });

        return $affected;
    }
}
