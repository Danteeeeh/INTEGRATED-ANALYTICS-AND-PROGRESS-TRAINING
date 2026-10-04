<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class HandleHttpsScheme
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the request is forwarded from a proxy/CDN as HTTPS
        $isForwardedHttps = $request->header('X-Forwarded-Proto') === 'https'
            || $request->header('X-Forwarded-Ssl') === 'on'
            || $request->header('Front-End-Https') === 'on';

        if ($isForwardedHttps) {
            // Force HTTPS scheme for URL generation
            URL::forceScheme('https');
            $request->server->set('HTTPS', 'on');
        }

        return $next($request);
    }
}
