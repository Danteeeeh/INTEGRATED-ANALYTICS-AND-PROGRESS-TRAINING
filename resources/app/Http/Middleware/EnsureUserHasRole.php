<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,instructor')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            abort(403, 'Your account is not active.');
        }

        if (! $user->hasRole(...$roles)) {
            abort(403, 'You are not authorized to access this resource.');
        }

        return $next($request);
    }
}
