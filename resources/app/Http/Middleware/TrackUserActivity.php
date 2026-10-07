<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tracks the user's last activity timestamp in the session and force-logs
 * them out once they have been idle longer than the configured timeout.
 *
 * This is the authoritative server-side guard; the client-side idle
 * watchdog in the layout is a UX convenience on top of it.
 */
class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeoutSeconds = ((int) config('lms.session_inactivity_timeout', 30)) * 60;
        $lastActivity = $request->session()->get('last_activity_at');

        if ($lastActivity && (now()->timestamp - (int) $lastActivity) > $timeoutSeconds) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', __('You were logged out due to inactivity. Please sign in again.'));
        }

        // Only persist once a minute to avoid writing on every request.
        $now = now()->timestamp;
        if (! $lastActivity || ($now - (int) $lastActivity) >= 60) {
            $request->session()->put('last_activity_at', $now);
        }

        return $next($request);
    }
}
