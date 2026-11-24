<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\LmsPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_renders(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot your password?', false);
    }

    public function test_reset_form_renders_with_the_email_from_the_link(): void
    {
        $this->get(route('password.reset', [
            'token' => 'test-token',
            'email' => 'student@example.com',
        ]))
            ->assertOk()
            ->assertSee('Set your password', false)
            ->assertSee('student@example.com', false);
    }

    public function test_password_setup_email_contains_the_reset_link(): void
    {
        $user = User::factory()->create(['email' => 'mail@example.com']);

        $mail = (new LmsPasswordReset('test-token'))->toMail($user);

        $this->assertSame('Set your LMS password', $mail->subject);
        $this->assertStringContainsString('/reset-password/test-token', $mail->actionUrl);
    }

    public function test_reset_link_is_emailed_for_an_existing_account(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        Notification::assertSentTo($user, LmsPasswordReset::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_unknown_email_gets_the_same_response_without_revealing_an_account(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_valid_token_sets_the_new_password_and_clears_the_token(): void
    {
        $user = User::factory()->create([
            'email' => 'new-password@example.com',
            'password' => 'old-password',
        ]);

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword!23',
            'password_confirmation' => 'NewPassword!23',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewPassword!23', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_invalid_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid-token@example.com',
            'password' => 'old-password',
        ]);

        $this->post(route('password.update'), [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'NewPassword!23',
            'password_confirmation' => 'NewPassword!23',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
