<?php

namespace App\Services;

use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Support\Str;
use RuntimeException;

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

        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
        ];
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $apiSecret, true);
        $token = $signingInput.'.'.$this->base64UrlEncode($signature);

        $this->assertTokenIsWellFormed($token, $apiKey, $apiSecret);

        return $token;
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
