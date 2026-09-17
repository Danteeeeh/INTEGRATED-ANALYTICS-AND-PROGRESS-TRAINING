<?php

namespace App\Http\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimit
{
    public function __construct(protected RateLimiter $limiter) {}

    public function handle(Request $request, callable $next, string $maxAttempts = '60', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($request, $key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts),
            $this->limiter->availableIn($key)
        );
    }

    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(
            $request->method().
            '|'.$request->server('SERVER_NAME').
            '|'.$request->ip().
            '|'.$request->path().
            '|'.$request->user()?->id ?? 'guest'
        );
    }

    protected function buildResponse(Request $request, string $key, int $maxAttempts): Response
    {
        $response = response()->json([
            'success' => false,
            'message' => 'Too many attempts. Please try again later.',
            'retry_after' => $this->limiter->availableIn($key),
        ], 429);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            0,
            $this->limiter->availableIn($key)
        );
    }

    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts, ?int $retryAfter = null): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        if (! is_null($retryAfter)) {
            $response->headers->add([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Reset' => $retryAfter,
            ]);
        }

        return $response;
    }

    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max($maxAttempts - $this->limiter->attempts($key), 0);
    }
}
