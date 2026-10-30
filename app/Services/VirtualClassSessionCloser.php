<?php

namespace App\Services;

use App\Models\VirtualClass;
use App\Models\VirtualClassAttendee;

/**
 * Closes out a finished virtual class.
 *
 * Ending a session has two side effects that must travel together:
 *
 *   1. Everyone still inside the room has to be disconnected. When the
 *      instructor ends the class from the dashboard nobody is in the room to
 *      broadcast the client-side "end meeting" packet, so the only reliable
 *      signal is RoomService.DeleteRoom on the server.
 *   2. Attendance rows that are still open (joined, never left) must be
 *      closed. A forced disconnect never fires the browser's leave beacon, so
 *      without this the durations stay wrong and the student keeps counting as
 *      "in room" forever.
 *
 * Both steps are best-effort: a LiveKit outage must never block closing the
 * class, because the class status alone already stops new tokens from being
 * issued.
 */
class VirtualClassSessionCloser
{
    public function __construct(private readonly LiveKitTokenService $tokens)
    {
    }

    public function close(VirtualClass $virtualClass): void
    {
        $this->disconnectRoom($virtualClass);
        $this->closeOpenAttendance($virtualClass);
    }

    /**
     * Delete the LiveKit room so every participant is dropped immediately.
     *
     * @return bool True when LiveKit confirmed the room is gone.
     */
    public function disconnectRoom(VirtualClass $virtualClass): bool
    {
        $roomName = (string) $virtualClass->livekit_room_name;

        if (! $virtualClass->isLiveKit() || $roomName === '') {
            return false;
        }

        return $this->tokens->deleteRoom($roomName);
    }

    /**
     * Mark every attendance row that is still open as left right now.
     *
     * @return int Number of attendance rows closed.
     */
    public function closeOpenAttendance(VirtualClass $virtualClass): int
    {
        $closed = 0;

        $virtualClass->attendees()
            ->whereNotNull('joined_at')
            ->whereNull('left_at')
            ->get()
            ->each(function (VirtualClassAttendee $attendee) use (&$closed) {
                $attendee->duration_minutes = (int) $attendee->duration_minutes
                    + max(0, (int) $attendee->joined_at->diffInMinutes(now()));
                $attendee->left_at = now();
                $attendee->save();

                $closed++;
            });

        return $closed;
    }
}
