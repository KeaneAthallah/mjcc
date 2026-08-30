<?php

namespace App\Services;

use App\Mail\VerifyEmailCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Issues, sends and verifies six-digit email verification codes.
 *
 * Security model:
 *  - the raw code is only passed to the Mailable and is never persisted,
 *    logged, or returned by the API — the database stores a hash only;
 *  - codes expire after ten minutes and are single-use;
 *  - previous active codes are invalidated whenever a new one is issued;
 *  - a limited number of failed verification attempts invalidate the code;
 *  - resend requests are throttled by a server-side cooldown in addition to
 *    the HTTP rate limiter applied to the route.
 */
final class EmailVerificationService
{
    public const CODE_LENGTH = 6;

    public const CODE_TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Issue a fresh verification code for the user, returning the raw code so
     * it can be handed to the Mailable. Any previously active code is
     * invalidated first.
     */
    public function issue(User $user): string
    {
        $this->assertMailTransportReady();

        EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $code = (string) random_int(10 ** (self::CODE_LENGTH - 1), 10 ** self::CODE_LENGTH - 1);

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'attempts' => 0,
            'is_active' => true,
        ]);

        return $code;
    }

    /**
     * Issue a new code and email it to the user.
     */
    public function send(User $user): void
    {
        $code = $this->issue($user);

        Mail::to($user)->send(new VerifyEmailCodeMail($user, $code));
    }

    /**
     * Send a new code, enforcing a cooldown since the last issued code.
     */
    public function resend(User $user): void
    {
        $latest = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        if ($latest !== null) {
            $nextAllowed = $latest->created_at->getTimestamp() + self::RESEND_COOLDOWN_SECONDS;
            $wait = max(0, $nextAllowed - now()->getTimestamp());

            if ($wait > 0) {
                throw ValidationException::withMessages([
                    'email' => ["Silakan tunggu {$wait} detik sebelum mengirim ulang kode."],
                ]);
            }
        }

        $this->send($user);
    }

    /**
     * Validate a submitted code for the user.
     *
     * @throws ValidationException when the code is invalid, expired or the
     *                             maximum attempt count has been reached.
     */
    public function verify(User $user, string $code): void
    {
        $record = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->where('is_active', true)
            ->latest('created_at')
            ->first();

        if ($record === null || $record->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => ['Kode verifikasi tidak valid.'],
            ]);
        }

        if (! Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');

            if ($record->attempts >= self::MAX_ATTEMPTS) {
                $record->update(['is_active' => false]);
            }

            throw ValidationException::withMessages([
                'code' => ['Kode verifikasi tidak valid.'],
            ]);
        }

        $record->forceFill([
            'verified_at' => now(),
            'is_active' => false,
        ])->save();

        $user->markEmailAsVerified();
    }

    /**
     * Refuse to silently "send" emails when the configured transport cannot
     * deliver. The `log` mailer is tolerated for local development, but never
     * in production.
     *
     * @throws RuntimeException
     */
    private function assertMailTransportReady(): void
    {
        $default = config('mail.default');

        if ($default === 'smtp'
            && (empty(config('mail.mailers.smtp.username')) || empty(config('mail.mailers.smtp.password')))) {
            throw new RuntimeException(
                'Konfigurasi email belum lengkap. Setel MAIL_USERNAME dan MAIL_PASSWORD '
                .'pada file .env agar kode verifikasi benar-benar terkirim.'
            );
        }

        if ($default === 'log' && app()->isProduction()) {
            throw new RuntimeException(
                'Transport email belum dikonfigurasi untuk produksi. '
                .'Setel MAIL_MAILER=smtp beserta kredensial MAIL_* pada file .env.'
            );
        }
    }
}
