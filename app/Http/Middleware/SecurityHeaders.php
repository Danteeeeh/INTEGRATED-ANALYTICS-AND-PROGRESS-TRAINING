<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // The built-in virtual classroom captures the camera and microphone
        // from this origin. An empty allowlist ("camera=()") tells the browser
        // to refuse getUserMedia, so the room could join but never publish.
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(self), geolocation=()');

        if (! app()->environment('local', 'testing') && $request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
