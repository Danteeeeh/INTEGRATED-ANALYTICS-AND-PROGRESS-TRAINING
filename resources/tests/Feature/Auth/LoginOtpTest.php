<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginOtpMail;
use App\Models\LoginVerification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

class LoginOtpTest extends TestCase
{
    use CreatesLmsUsers;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('lms.login_otp.enabled', true);
        Mail::fake();
    }

    public function test_valid_password_requires_otp_before_authentication(): void
    {
        $user = $this->makeUser('student', ['email' => 'otp@example.com', 'password' => 'password123']);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login.verify'));
        $this->assertGuest();
        $this->assertDatabaseHas('login_verifications', ['user_id' => $user->id, 'consumed_at' => null]);
        Mail::assertSent(LoginOtpMail::class, fn (LoginOtpMail $mail) => $mail->hasTo($user->email));
    }

    public function test_gmail_admin_account_still_requires_otp_and_reaches_admin_dashboard(): void
    {
        $admin = $this->makeUser('admin', ['email' => 'johncedrickdayandante6@gmail.com', 'password' => 'Password123!']);

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'Password123!'])
            ->assertRedirect(route('login.verify'));
        $this->assertGuest();

        $mail = Mail::sent(LoginOtpMail::class)->first();
        $this->post(route('login.verify'), ['code' => $mail->code])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_gmail_email_normalization_still_requires_otp(): void
    {
        $user = $this->makeUser('admin', ['email' => 'johncedrickdayandante6@gmail.com', 'password' => 'Password123!']);

        $this->post(route('login'), [
            'email' => '  JOHNcedrickdayandante6@GMAIL.COM  ',
            'password' => 'Password123!',
        ])->assertRedirect(route('login.verify'));

        $this->assertGuest();
    }

    public function test_correct_otp_authenticates_and_consumes_code(): void
    {
        $user = $this->makeUser('student', ['email' => 'otp-success@example.com', 'password' => 'password123']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123']);
        $mail = Mail::sent(LoginOtpMail::class)->first();

        $response = $this->post(route('login.verify'), ['code' => $mail->code]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(LoginVerification::firstOrFail()->fresh()->consumed_at);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = $this->makeUser('student', ['email' => 'otp-expired@example.com', 'password' => 'password123']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123']);
        $verification = LoginVerification::firstOrFail();
        $mail = Mail::sent(LoginOtpMail::class)->first();
        $verification->update(['expires_at' => now()->subMinute()]);

        $this->post(route('login.verify'), ['code' => $mail->code])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_otp_attempt_limit_rejects_further_codes(): void
    {
        config()->set('lms.login_otp.max_attempts', 2);
        $user = $this->makeUser('student', ['email' => 'otp-attempts@example.com', 'password' => 'password123']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123']);
        $verification = LoginVerification::firstOrFail();

        $this->post(route('login.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post(route('login.verify'), ['code' => '111111'])->assertSessionHasErrors('code');

        $this->assertNotNull($verification->fresh()->consumed_at);
        $this->assertGuest();
    }
}
