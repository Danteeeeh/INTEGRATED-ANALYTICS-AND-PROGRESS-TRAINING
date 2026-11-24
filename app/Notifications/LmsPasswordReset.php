<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * One notification for both first-time activation and a normal password reset.
 * New AA accounts get a random password, so this link is the only way in until
 * the student/faculty member sets one.
 */
class LmsPasswordReset extends BaseResetPassword
{
    public function __construct(string $token, private bool $activation = false)
    {
        parent::__construct($token);
    }

    public function toMail($notifiable)
    {
        $expires = (int) config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire',
            60
        );

        $name = trim((string) ($notifiable->first_name ?? ''));

        return (new MailMessage)
            ->subject('Set your LMS password')
            ->greeting($name !== '' ? "Hello {$name}," : 'Hello,')
            ->line($this->activation
                ? 'Your LMS account has been created. Set your password to finish activating it.'
                : 'A password setup or reset was requested for your LMS account.')
            ->action('Set your password', $this->resetUrl($notifiable))
            ->line("This link expires in {$expires} minutes.")
            ->line('If you did not request this, you can safely ignore this email.');
    }
}
