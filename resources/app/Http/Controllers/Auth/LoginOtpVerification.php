<?php
namespace App\Http\Controllers\Auth;
use App\Mail\LoginOtpMail;
use App\Models\LoginVerification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
trait LoginOtpVerification
{
    public function verifyOtp(Request $request): RedirectResponse
    {
        $p = $this->pendingUser($request);
        if (! $p) return redirect()->route('login')->withErrors(['email' => 'Your login session expired.']);
        $code = $request->validate(['code' => ['required', 'digits:6']])['code'];
        $v = LoginVerification::where('user_id', $p['user_id'])->whereNull('consumed_at')->latest('id')->first();
        $max = (int) config('lms.login_otp.max_attempts', 5);
        if (! $v || ! $v->isUsable()) { $this->clearPendingOtp($request); return back()->withErrors(['code' => 'This code has expired.']); }
        if ($v->attempts >= $max) { $v->update(['consumed_at' => now()]); $this->clearPendingOtp($request); return back()->withErrors(['code' => 'Too many attempts.']); }
        if (! Hash::check($code, $v->code_hash)) { $v->increment('attempts'); $v->refresh(); if ($v->attempts >= $max) $v->update(['consumed_at' => now()]); return back()->withErrors(['code' => 'Incorrect verification code.']); }
        $v->update(['consumed_at' => now()]);
        $user = User::find($p['user_id']);
        if (! $user || ! $user->isActive()) { $this->clearPendingOtp($request); return redirect()->route('login')->withErrors(['email' => 'Your account is no longer active.']); }
        Auth::login($user, (bool) ($p['remember'] ?? false));
        $request->session()->regenerate(); $request->session()->forget('login_otp');
        app(\App\Services\LoginThrottleService::class)->clear($user->email);
        $user->forceFill(['last_login_at' => now()])->save();
        return redirect($this->dashboardRouteFor($user->role?->slug));
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $p = $this->pendingUser($request); if (! $p) return redirect()->route('login');
        $user = User::find($p['user_id']); if (! $user || ! $user->isActive()) { $this->clearPendingOtp($request); return redirect()->route('login'); }
        $v = LoginVerification::where('user_id', $user->id)->whereNull('consumed_at')->latest('id')->first();
        $wait = (int) config('lms.login_otp.resend_cooldown_seconds', 60);
        if ($v?->last_sent_at && $v->last_sent_at->addSeconds($wait)->isFuture()) return back()->withErrors(['code' => 'Please wait before resending.']);
        $this->beginOtpChallenge($request, $user, (bool) ($p['remember'] ?? false));
        return back()->with('status', 'A new verification code has been sent.');
    }

    protected function beginOtpChallenge(Request $request, User $user, bool $remember): void
    {
        LoginVerification::where('user_id', $user->id)->whereNull('consumed_at')->update(['consumed_at' => now()]);
        $length = (int) config('lms.login_otp.code_length', 6);
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        $expires = (int) config('lms.login_otp.expires_minutes', 10);
        LoginVerification::create(['user_id' => $user->id, 'code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes($expires), 'last_sent_at' => now()]);
        $request->session()->put('login_otp', ['user_id' => $user->id, 'email' => $user->email, 'remember' => $remember, 'created_at' => now()->timestamp]);
        // NOTE: intentionally no session()->regenerate() here. The user is still a guest, and
        // regenerating mid-flow (e.g. on every resend) makes the CSRF token in an already
        // rendered verify page stale -> 419. The real regeneration happens after Auth::login().
        Mail::to($user->email)->send(new LoginOtpMail($user, $code, $expires));
    }

    protected function pendingUser(Request $request): ?array
    {
        $pending = $request->session()->get('login_otp');
        if (! is_array($pending) || empty($pending['user_id']) || empty($pending['created_at'])) return null;
        if ((int) $pending['created_at'] < now()->subMinutes((int) config('lms.login_otp.pending_session_minutes', 15))->timestamp) { $this->clearPendingOtp($request); return null; }
        return $pending;
    }

    protected function clearPendingOtp(Request $request): void { $request->session()->forget('login_otp'); }

    protected function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = substr($local, 0, min(2, strlen($local)));
        return $visible.str_repeat('*', max(1, strlen($local) - strlen($visible))).'@'.$domain;
    }
}
