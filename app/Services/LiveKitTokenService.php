<?php

namespace App\Services;

use App\Models\User;
use App\Models\VirtualClass;
use RuntimeException;

/**
 * Creates short-lived LiveKit participant JWTs without requiring a separate
 * Node.js service. API secrets remain on the Laravel server.
 */
class LiveKitTokenService
{
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
            'nbf' => $now - 5,
            'iat' => $now,
            'exp' => $now + 1800,
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

        return $signingInput.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
