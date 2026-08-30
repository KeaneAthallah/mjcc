<?php

namespace App\Mail;

use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Delivers the six-digit email verification code to a newly registered user.
 *
 * The raw code is intentionally only present inside this message; it is never
 * persisted, logged, or exposed through the API.
 */
class VerifyEmailCodeMail extends Mailable
{
    public function __construct(
        public readonly User $user,
        public readonly string $code,
        public readonly int $expiresInMinutes = EmailVerificationService::CODE_TTL_MINUTES,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode Verifikasi Email Anda — MJCC',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email-code',
        );
    }
}
