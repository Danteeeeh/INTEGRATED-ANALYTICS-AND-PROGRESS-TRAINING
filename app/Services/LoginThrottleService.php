<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Per-account login attempt throttling backed by the users table.
 *
 * Complements the per-IP Laravel throttle middleware: this one tracks a
 * single account (by email) regardless of which IP the requests come from,
 * and locks the account for a configurable window after N failures.
 */
class LoginThrottleService
{
    public function maxAttempts(): int
    {
        return (int) config('lms.login_max_attempts', 5);
    }

    public function lockoutMinutes(): int
    {
        return (int) config('lms.login_lockout_minutes', 15);
    }

    /**
     * Throw a ValidationException when the account is currently locked.
     * Safe to call before every login attempt.
     *
     * If a previous lock has expired, resets the counters so the user gets
     * a clean slate for their next attempt.
     */
    public function ensureNotLocked(string $email): void
    {
        $email = $this->normalizeEmail($email);
        $user = $this->findUser($email);

        if (! $user) {
            return;
        }

        if ($user->locked_until && now()->lessThan($user->locked_until)) {
            $minutes = (int) ceil(now()->diffInMinutes($user->locked_until));

            throw ValidationException::withMessages([
                'email' => __('Too many failed login attempts. Try again in :minutes minute(s).', ['minutes' => $minutes]),
            ]);
        }

        // Lock window over: reset so the next attempt starts fresh.
        if ($user->locked_until && now()->greaterThanOrEqualTo($user->locked_until)) {
            $this->clear($email);
        }
    }

    /**
     * Record a failed login attempt. Locks the account once the threshold
     * is reached. Returns the current failure count.
     *
     * Uses an atomic DB increment so parallel requests cannot race past
     * the threshold (read-then-write on the Eloquent model is racy).
     */
    public function registerFailure(string $email): int
    {
        $email = $this->normalizeEmail($email);
        $maxAttempts = max(1, $this->maxAttempts());

        return DB::transaction(function () use ($email, $maxAttempts): int {
            $user = User::where('email', $email)->lockForUpdate()->first();

            if (! $user) {
                return 0;
            }

            $now = now();

            // If a previous lock has expired, start the count over so the user
            // gets a full fresh window (not re-locked after one attempt).
            if ($user->locked_until && $now->greaterThanOrEqualTo($user->locked_until)) {
                $user->failed_login_attempts = 0;
                $user->locked_until = null;
                $user->last_failed_login_at = null;
            }

            $attempts = min($maxAttempts, ((int) $user->failed_login_attempts) + 1);
            $user->forceFill([
                'failed_login_attempts' => $attempts,
                'last_failed_login_at' => $now,
                'locked_until' => $attempts >= $maxAttempts
                    ? $now->copy()->addMinutes($this->lockoutMinutes())
                    : null,
            ])->save();

            return $attempts;
        });
    }

    /**
     * Reset the counters after a successful login.
     */
    public function clear(string $email): void
    {
        $email = $this->normalizeEmail($email);
        $user = $this->findUser($email);

        if (! $user) {
            return;
        }

        DB::table('users')->where('id', $user->id)->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_failed_login_at' => null,
        ]);
    }

    protected function findUser(string $email): ?User
    {
        return User::where('email', $this->normalizeEmail($email))->first();
    }

    protected function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
