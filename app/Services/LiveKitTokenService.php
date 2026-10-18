<?php

namespace App\Services;

use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Creates short-lived LiveKit participant JWTs without requiring a separate
 * Node.js service. API secrets remain on the Laravel server.
 */
class LiveKitTokenService
{
    /**
     * Human-readable setup problems with the LiveKit configuration. Returns
     * null when everything looks right. These messages are what the Super
     * Admin sees instead of a bare "invalid token" from the LiveKit client.
     */
    public function diagnose(): ?string
    {
        $url = (string) config('services.livekit.url');
        $key = (string) config('services.livekit.api_key');
        $secret = (string) config('services.livekit.api_secret');

        $missing = [];
        if ($url === '') $missing[] = 'LIVEKIT_URL';
        if ($key === '') $missing[] = 'LIVEKIT_API_KEY';
        if ($secret === '') $missing[] = 'LIVEKIT_API_SECRET';

        if ($missing !== []) {
            return 'LiveKit is not configured — '.implode(', ', $missing).' '.($missing === ['LIVEKIT_URL'] ? 'is' : 'are').' empty. '
                .'Set the LIVEKIT_* values in the HostForge environment variables, then redeploy '
                .'(or run "php artisan config:clear") so the new values are picked up — a cached config keeps serving the old empty values.';
        }

        if (! Str::startsWith($url, ['ws://', 'wss://'])) {
            return 'LIVEKIT_URL must be the WebSocket URL of your LiveKit server (for LiveKit Cloud it looks like wss://your-project.livekit.cloud) — '
                .'it is currently set to "'.$url.'". Using an http(s) address or the LiveKit Cloud dashboard URL will fail the signal connection.';
        }

        if (strlen($key) < 8) {
            return 'LIVEKIT_API_KEY looks too short ('.strlen($key).' characters) — copy the full API key from the LiveKit Cloud project settings.';
        }

        if (strlen($secret) < 16) {
            return 'LIVEKIT_API_SECRET looks too short ('.strlen($secret).' characters) — copy the full API secret from the LiveKit Cloud project settings.';
        }

        return null;
    }

    /**
     * Create the join token. Hardened against the two environmental causes of
     * "invalid token" that are not wrong credentials:
     *   1. clock skew — `nbf` allows 60s of slack instead of 5s;
     *   2. replay detection — every token carries a unique `jti`.
     * The claim set itself stays on the official LiveKit spec:
     * iss (API key), sub (identity), exp/nbf/iat, video grant.
     */
    public function createJoinToken(User $user, VirtualClass $virtualClass): string
    {
        $apiKey = (string) config('services.livekit.api_key');
        $apiSecret = (string) config('services.livekit.api_secret');
        $roomName = (string) $virtualClass->livekit_room_name;

        if ($apiKey === '' || $apiSecret === '' || $roomName === '') {
            throw new RuntimeException('LiveKit is not configured for this class.');
        }

        $now = time();
        $payload = [
            'iss' => $apiKey,
            'sub' => 'user-'.$user->getKey(),
            'name' => $user->full_name ?: $user->name,
            'nbf' => $now - 60,
            'iat' => $now,
            'exp' => $now + 1800,
            'jti' => bin2hex(random_bytes(8)),
            'video' => [
                'roomJoin' => true,
                'room' => $roomName,
                'canPublish' => true,
                'canSubscribe' => true,
                'canPublishData' => true,
            ],
        ];

        $token = $this->signJwt($payload, $apiKey, $apiSecret);

        $this->assertTokenIsWellFormed($token, $apiKey, $apiSecret);

        return $token;
    }

    /**
     * HTTPS origin for the RoomService Twirp API. The configured LIVEKIT_URL
     * is a WebSocket URL (wss://...) for the browser signal connection; the
     * server API speaks plain HTTPS on the same host.
     */
    public function httpBaseUrl(): ?string
    {
        $url = rtrim((string) config('services.livekit.url'), '/');

        if ($url === '') {
            return null;
        }

        if (Str::startsWith($url, 'wss://')) {
            return 'https://'.Str::after($url, 'wss://');
        }

        if (Str::startsWith($url, 'ws://')) {
            return 'http://'.Str::after($url, 'ws://');
        }

        if (Str::startsWith($url, ['https://', 'http://'])) {
            return $url;
        }

        return null;
    }

    /**
     * Short-lived token for server-side RoomService calls. ListParticipants
     * requires the roomAdmin grant scoped to the exact room — roomList is for
     * ListRooms and is rejected by the participant listing endpoint.
     */
    public function createRoomAdminToken(string $roomName, int $ttlSeconds = 60): ?string
    {
        $apiKey = (string) config('services.livekit.api_key');
        $apiSecret = (string) config('services.livekit.api_secret');

        if ($apiKey === '' || $apiSecret === '' || $roomName === '') {
            return null;
        }

        $now = time();

        return $this->signJwt([
            'iss' => $apiKey,
            'sub' => 'lms-server',
            'nbf' => $now - 60,
            'iat' => $now,
            'exp' => $now + max(30, $ttlSeconds),
            'jti' => bin2hex(random_bytes(8)),
            'video' => [
                'roomAdmin' => true,
                'room' => $roomName,
            ],
        ], $apiKey, $apiSecret);
    }

    /**
     * Exact number of participants connected to a LiveKit room, straight from
     * RoomService.ListParticipants (not from attendance rows, which go stale
     * when a browser closes without posting its leave beacon).
     *
     * Returns null when LiveKit is unconfigured, unreachable, or answers with
     * an error so the caller can fall back to the attendance estimate instead
     * of failing the page.
     */
    public function liveParticipantCount(string $roomName): ?int
    {
        $participants = $this->liveParticipants($roomName);

        return $participants === null ? null : count($participants);
    }

    /**
     * List the participants currently connected to a LiveKit room.
     *
     * Each entry is ['identity' => string, 'name' => string, 'joined_at' => ?string].
     * Identity is the 'user-<id>' we mint in the JWT, so the numeric suffix
     * maps to a User id; name is the display name from the token; joined_at is
     * the RFC 3339 connect time LiveKit reports (null when it omits one).
     *
     * Returns null when LiveKit is unconfigured, unreachable, or answers with
     * an error.
     */
    public function liveParticipants(string $roomName): ?array
    {
        $response = $this->roomServicePost('ListParticipants', $roomName);

        if ($response === null) {
            return null;
        }

        $participants = $response->json('participants');

        if (! is_array($participants)) {
            return null;
        }

        // ParticipantInfo.State: 0 JOINING, 1 JOINED, 2 ACTIVE, 3 DISCONNECTED.
        // A closing tab can linger as DISCONNECTED for a moment; it is not
        // someone still in the room.
        $active = array_filter($participants, function ($participant) {
            if (! is_array($participant)) {
                return false;
            }

            $state = $participant['state'] ?? null;

            return $state !== 3 && $state !== 'DISCONNECTED';
        });

        return array_values(array_map(function ($participant) {
            return [
                'identity' => (string) ($participant['identity'] ?? ''),
                'name' => (string) ($participant['name'] ?? ''),
                'joined_at' => $this->participantJoinedAt($participant),
            ];
        }, $active));
    }

    /**
     * Connect time from a ParticipantInfo payload. LiveKit serialises the
     * protobuf Timestamp as RFC 3339, so accept that (and the odd alternate
     * spelling) and return null rather than guessing when it is absent.
     */
    protected function participantJoinedAt(array $participant): ?string
    {
        $raw = $participant['joinedAt'] ?? $participant['joined_at'] ?? null;

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * End the room for everyone.
     *
     * RoomService.DeleteRoom disconnects every participant and destroys the
     * room, which is what makes a dashboard "End" reach students who never
     * receive an in-room data message (nobody is inside to publish it).
     *
     * Returns true when LiveKit confirmed the deletion. A false result is not
     * fatal: the class status is already closed, so no fresh token can be
     * issued and rejoin attempts fail.
     */
    public function deleteRoom(string $roomName): bool
    {
        return $this->roomServicePost('DeleteRoom', $roomName) !== null;
    }

    /**
     * POST a RoomService Twirp method with a short-lived roomAdmin token.
     *
     * Returns null when LiveKit is unconfigured, unreachable, or answers with
     * an error, so callers can degrade instead of failing the request.
     */
    protected function roomServicePost(string $method, string $roomName): ?Response
    {
        if ($roomName === '' || $this->diagnose() !== null) {
            return null;
        }

        $baseUrl = $this->httpBaseUrl();
        $token = $this->createRoomAdminToken($roomName);

        if ($baseUrl === null || $token === null) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(1)
                ->timeout(2)
                ->post($baseUrl.'/twirp/livekit.RoomService/'.$method, ['room' => $roomName]);
        } catch (Throwable $exception) {
            Log::debug('LiveKit '.$method.' request failed', [
                'room' => $roomName,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::debug('LiveKit '.$method.' returned an error', [
                'room' => $roomName,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response;
    }

    /**
     * Encode and sign a LiveKit JWT (HS256).
     */
    protected function signJwt(array $payload, string $apiKey, string $apiSecret): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
        ];
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $apiSecret, true);

        return $signingInput.'.'.$this->base64UrlEncode($signature);
    }

    /**
     * Prove the token we just signed actually verifies with the configured
     * secret before it reaches the browser. A malformed token fails here with
     * a clear message instead of surfacing as "invalid token" in the room.
     */
    protected function assertTokenIsWellFormed(string $token, string $apiKey, string $apiSecret): void
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('LiveKit token generation produced a malformed token.');
        }

        [$head, $body, $signature] = $parts;
        $expected = $this->base64UrlEncode(hash_hmac('sha256', $head.'.'.$body, $apiSecret, true));
        if (! hash_equals($expected, $signature)) {
            throw new RuntimeException('LiveKit token signature verification failed — the API key and API secret do not belong together.');
        }

        $claims = json_decode($this->base64UrlDecode($body), true);
        if (! is_array($claims) || ($claims['iss'] ?? null) !== $apiKey) {
            throw new RuntimeException('LiveKit token claims are malformed.');
        }
    }

    protected function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/'));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
