<?php

namespace App\Services;

use App\Models\VirtualClass;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * How a virtual classroom filled up and emptied over its session.
 *
 * The data is the LMS's own attendance rows: a row is opened when a room token
 * is issued, and closed on the leave call or on a server-side end. That gives a
 * complete session history with no extra storage — LiveKit's RoomService only
 * reports who is connected at the moment it is asked.
 *
 * The consequence to keep in mind: an attendance row is "said they were in the
 * room", so a participant who connected through a stale token is counted for a
 * few seconds. The live headcount (VirtualClassPresence) is the number to trust
 * for "right now"; this is the shape of the session.
 */
class VirtualClassRoomTimeline
{
    /** Points on the sparkline. Twenty-four keeps the payload tiny. */
    public const DEFAULT_POINTS = 24;

    /** Never chart more than this much of a long-running session. */
    public const DEFAULT_WINDOW_MINUTES = 60;

    public const DEFAULT_EVENT_LIMIT = 8;

    /**
     * @return array{
     *     points: array<int, array{at: string, count: int}>,
     *     peak: int,
     *     now: int,
     *     events: array<int, array{type: string, name: string, role: string, identity: string, at: string}>
     * }
     */
    public function snapshot(
        VirtualClass $virtualClass,
        int $points = self::DEFAULT_POINTS,
        int $windowMinutes = self::DEFAULT_WINDOW_MINUTES,
        int $eventLimit = self::DEFAULT_EVENT_LIMIT,
    ): array {
        // One read of the session's attendance rows answers both questions.
        $attendees = $virtualClass->attendees()
            ->whereNotNull('joined_at')
            ->with('user')
            ->orderBy('joined_at')
            ->get();

        return [
            ...$this->sparkline($virtualClass, $attendees, $points, $windowMinutes),
            'events' => $this->events($virtualClass, $attendees, $eventLimit),
        ];
    }

    /**
     * Headcount per bucket from the session start (or the window edge for a
     * session running longer than the window) up to now.
     *
     * @param  Collection<int, \App\Models\VirtualClassAttendee>  $attendees
     * @return array{points: array<int, array{at: string, count: int}>, peak: int, now: int}
     */
    protected function sparkline(VirtualClass $virtualClass, Collection $attendees, int $points, int $windowMinutes): array
    {
        $to = Carbon::now();
        $windowStart = $to->copy()->subMinutes(max(1, $windowMinutes));

        // A slow first ten minutes is part of the session's shape, not noise to
        // hide, so the curve starts at the scheduled start rather than at the
        // first join. A record without a schedule falls back to that first join.
        $from = $virtualClass->startsAt() ?? $attendees->min('joined_at') ?? $to->copy()->subMinute();

        if ($from->lessThan($windowStart)) {
            $from = $windowStart;
        }

        if ($from->greaterThan($to)) {
            $from = $to->copy()->subMinute();
        }

        $steps = max(1, $points);
        $span = max(60, $to->getTimestamp() - $from->getTimestamp());

        $series = [];

        for ($step = 0; $step <= $steps; $step++) {
            $at = $from->copy()->addSeconds((int) round($span * $step / $steps));
            $series[] = ['at' => $at->toIso8601String(), 'count' => $this->presentAt($attendees, $at)];
        }

        $counts = array_column($series, 'count');

        return [
            'points' => $series,
            'peak' => $counts === [] ? 0 : max($counts),
            'now' => $counts === [] ? 0 : end($counts),
        ];
    }

    /**
     * Students in the room at an instant: joined by then, not yet gone.
     *
     * @param  Collection<int, \App\Models\VirtualClassAttendee>  $attendees
     */
    protected function presentAt(Collection $attendees, Carbon $at): int
    {
        return $attendees->filter(function ($attendee) use ($at) {
            if ($attendee->joined_at === null || $attendee->joined_at->greaterThan($at)) {
                return false;
            }

            return $attendee->left_at === null || $attendee->left_at->greaterThan($at);
        })->count();
    }

    /**
     * Newest joins and leaves first, straight from the attendance rows.
     *
     * @param  Collection<int, \App\Models\VirtualClassAttendee>  $attendees
     * @return array<int, array{type: string, name: string, role: string, identity: string, at: string}>
     */
    protected function events(VirtualClass $virtualClass, Collection $attendees, int $limit): array
    {
        $events = [];

        foreach ($attendees as $attendee) {
            $userId = (int) $attendee->user_id;
            $identity = 'user-'.$userId;
            $name = $attendee->user?->full_name ?: 'User #'.$userId;
            $role = $this->role($virtualClass, $userId, $attendee->user !== null);

            if ($attendee->joined_at !== null) {
                $events[] = ['type' => 'join', 'name' => $name, 'role' => $role, 'identity' => $identity, 'at' => $attendee->joined_at];
            }

            if ($attendee->left_at !== null) {
                $events[] = ['type' => 'leave', 'name' => $name, 'role' => $role, 'identity' => $identity, 'at' => $attendee->left_at];
            }
        }

        usort($events, fn (array $a, array $b) => $b['at']->getTimestamp() <=> $a['at']->getTimestamp());

        return array_map(function (array $event) {
            $event['at'] = $event['at']->toIso8601String();

            return $event;
        }, array_slice($events, 0, max(1, $limit)));
    }

    protected function role(VirtualClass $virtualClass, int $userId, bool $known): string
    {
        $instructorId = $virtualClass->class?->instructor_id;

        if ($instructorId !== null && $userId === (int) $instructorId) {
            return 'instructor';
        }

        return $known ? 'student' : 'participant';
    }
}
