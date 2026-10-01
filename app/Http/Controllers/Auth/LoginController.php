<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Mail\LoginOtpMail;
use App\Models\LoginVerification;
use App\Models\Role;
use App\Models\User;
use App\Services\LoginThrottleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    use LoginOtpVerification;
    public function create()
    {
        return view('auth.login');
    }

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $min = (int) config('lms.password_min_length', 8);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::min($min)],
        ]);

        $studentRole = Role::where('slug', Role::STUDENT)->firstOrFail();

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => $studentRole->id,
            'status' => 'active',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $throttle = app(LoginThrottleService::class);
        $email = mb_strtolower(trim((string) $request->string('email')));
        $credentials = ['email' => $email, 'password' => (string) $request->string('password')];
        $throttle->ensureNotLocked($email);

        if (! Auth::validate($credentials)) {
            $throttle->registerFailure($email);
            throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]);
        }

        $user = User::where('email', $email)->firstOrFail();
        if (! $user->isActive()) {
            throw ValidationException::withMessages(['email' => __('Your account has been deactivated. Contact an administrator.')]);
        }

        $otpExempt = in_array($email, config('lms.login_otp.exempt_emails', []), true);
        if (! config('lms.login_otp.enabled', true) || $otpExempt) {
            Auth::login($user, $request->boolean('remember'));
            $throttle->clear($email);
            $request->session()->regenerate();
            $user->forceFill(['last_login_at' => now()])->save();
            return redirect($this->dashboardRouteFor($user->role?->slug));
        }

        $this->beginOtpChallenge($request, $user, $request->boolean('remember'));
        return redirect()->route('login.verify');
    }

    public function showOtpForm(Request $request): View|RedirectResponse
    {
        $pending = $this->pendingUser($request);
        if (! $pending) return redirect()->route('login');
        $verification = LoginVerification::where('user_id', $pending['user_id'])->whereNull('consumed_at')->latest('id')->first();
        if (! $verification || ! $verification->isUsable()) {
            $this->clearPendingOtp($request);
            return redirect()->route('login')->withErrors(['email' => 'Your verification code expired. Please sign in again.']);
        }
        return view('auth.verify-login', [
            'maskedEmail' => $this->maskEmail($pending['email']),
            'expiresInMinutes' => (int) max(1, ceil(now()->diffInMinutes($verification->expires_at))),
            'resendCooldown' => (int) config('lms.login_otp.resend_cooldown_seconds', 60),
            'lastSentAt' => $verification->last_sent_at,
            'resendWait' => $verification->last_sent_at
                ? (int) max(0, ((int) config('lms.login_otp.resend_cooldown_seconds', 60)) - ceil(now()->diffInSeconds($verification->last_sent_at)))
                : 0,
        ]);
    }

    public function keepAlive(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $this->clearPendingOtp($request);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    protected function dashboardRouteFor(?string $roleSlug): string
    {
        return match ($roleSlug) {
            Role::ADMIN => route('admin.dashboard'),
            Role::INSTRUCTOR => route('instructor.dashboard'),
            Role::STUDENT => route('student.dashboard'),
            default => '/login',
        };
    }
}
