<?php

namespace App\Services;

use App\Models\User;
use App\Models\VirtualClass;

/**
 * Who is inside a running virtual classroom right now.
 *
 * LiveKit's RoomService is the source of truth: attendance rows only close when
 * a browser posts its leave beacon, so a closed tab or a crashed LTI session
 * leaves a student counted as present forever. Attendance rows are the
 * fallback when LiveKit is unconfigured, unreachable, or erroring.
 *
 * Identities are resolved against the LMS, so the roster shows real names and
 * roles instead of whatever the client claimed inside its join token.
 */
class VirtualClassPresence
{
    public function __construct(private LiveKitTokenService $tokens)
    {
    }

    /**
     * @return array{
     *     count: int,
     *     exact: bool,
     *     participants: array<int, array{identity: string, name: string, role: string, is_instructor: bool, joined_at: ?string}>
     * }
     */
    public function snapshot(VirtualClass $virtualClass): array
    {
        if (! $virtualClass->isLiveNow()) {
            return ['count' => 0, 'exact' => false, 'participants' => []];
        }

        // One ListParticipants call answers both "how many" and "who" — asking
        // LiveKit twice per poll was the previous shape of this code.
        $live = $this->tokens->liveParticipants((string) $virtualClass->livekit_room_name);

        if ($live !== null) {
            $participants = $this->annotate($live, $virtualClass);

            return ['count' => count($participants), 'exact' => true, 'participants' => $participants];
        }

        $participants = $this->fromAttendance($virtualClass);

        return ['count' => count($participants), 'exact' => false, 'participants' => $participants];
    }

    /**
     * Merge LiveKit's participant list with our own user records.
     *
     * @param  array<int, array{identity: string, name: string, joined_at: ?string}>  $live
     */
    protected function annotate(array $live, VirtualClass $virtualClass): array
    {
        $instructorId = $this->instructorId($virtualClass);

        $ids = collect($live)
            ->map(fn (array $participant) => $this->userId($participant['identity']))
            ->filter()
            ->unique()
            ->values();

        $users = $ids->isEmpty()
            ? collect()
            : User::whereIn('id', $ids)->get()->keyBy('id');

        return collect($live)->map(function (array $participant) use ($users, $instructorId) {
            $userId = $this->userId($participant['identity']);
            $user = $userId ? $users->get($userId) : null;
            $name = $user?->full_name ?: trim((string) $participant['name']);

            return [
                'identity' => $participant['identity'],
                'name' => $name !== '' ? $name : 'Participant',
                'role' => $this->role($userId, $user !== null, $instructorId),
                'is_instructor' => $userId !== null && $userId === $instructorId,
                'joined_at' => $participant['joined_at'] ?? null,
            ];
        })->values()->all();
    }

    /**
     * Fallback roster built from open attendance rows (joined, not left).
     */
    protected function fromAttendance(VirtualClass $virtualClass): array
    {
        $instructorId = $this->instructorId($virtualClass);

        return $virtualClass->attendees()
            ->whereNotNull('joined_at')
            ->whereNull('left_at')
            ->with('user')
            ->get()
            ->map(function ($attendee) use ($instructorId) {
                $userId = (int) $attendee->user_id;

                return [
                    'identity' => 'user-'.$userId,
                    'name' => $attendee->user?->full_name ?: 'User #'.$userId,
                    'role' => $this->role($userId, $attendee->user !== null, $instructorId),
                    'is_instructor' => $userId === $instructorId,
                    'joined_at' => $attendee->joined_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    protected function role(?int $userId, bool $known, ?int $instructorId): string
    {
        if ($userId === null) {
            return 'participant';
        }

        if ($userId === $instructorId) {
            return 'instructor';
        }

        return $known ? 'student' : 'participant';
    }

    /**
     * The numeric user id inside a 'user-<id>' identity, or null for identities
     * we did not mint (a server token, an external observer).
     */
    protected function userId(string $identity): ?int
    {
        if (! preg_match('/^user-(\d+)$/', $identity, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    protected function instructorId(VirtualClass $virtualClass): ?int
    {
        $instructorId = $virtualClass->class?->instructor_id;

        return $instructorId === null ? null : (int) $instructorId;
    }
}
