<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->data['title'] ?? 'Notification',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            with: [
                'title' => $this->data['title'] ?? 'Notification',
                'message' => $this->data['message'] ?? '',
                'type' => $this->data['type'] ?? 'info',
                'link' => $this->data['link'] ?? null,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
