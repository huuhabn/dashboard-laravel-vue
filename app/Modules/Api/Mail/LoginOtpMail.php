<?php

declare(strict_types=1);

namespace App\Modules\Api\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $userName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Sign-in code'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-otp',
            with: [
                'code' => $this->code,
                'userName' => $this->userName,
            ],
        );
    }
}
