<?php

use App\Mail\VerifyEmailCodeMail;
use App\Models\ActivityLog;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Mail;

function issueCodeFor(User $user): string
{
    return app(EmailVerificationService::class)->issue($user);
}

function captureLastCode(): ?string
{
    $code = null;

    Mail::assertSent(VerifyEmailCodeMail::class, function (VerifyEmailCodeMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    return $code;
}

it('verifies a valid code end-to-end after registration', function () {
    Mail::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Budi Santoso',
        'email' => 'verify@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertStatus(201);

    $code = captureLastCode();

    $this->postJson('/api/v1/email/verify', [
        'email' => 'verify@example.com',
        'code' => $code,
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Email berhasil diverifikasi. Silakan masuk menggunakan akun Anda.');

    $user = User::query()->where('email', 'verify@example.com')->first();

    expect($user->hasVerifiedEmail())->toBeTrue();

    $record = EmailVerificationCode::query()->where('user_id', $user->id)->first();

    expect($record->verified_at)->not->toBeNull()
        ->and($record->is_active)->toBeFalse();
});

it('rejects an invalid code', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create();
    issueCodeFor($user);

    $this->postJson('/api/v1/email/verify', [
        'email' => $user->email,
        'code' => '000001',
    ])->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors(['code']);

    expect(EmailVerificationCode::query()->where('user_id', $user->id)->first()->attempts)->toBe(1)
        ->and($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects an expired code', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create();
    $code = issueCodeFor($user);

    $this->travel(11)->minutes();

    $this->postJson('/api/v1/email/verify', [
        'email' => $user->email,
        'code' => $code,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['code']);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('prevents reusing a code after successful verification', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create();
    $code = issueCodeFor($user);

    $this->postJson('/api/v1/email/verify', ['email' => $user->email, 'code' => $code])->assertOk();

    $this->postJson('/api/v1/email/verify', ['email' => $user->email, 'code' => $code])
        ->assertOk()
        ->assertJsonPath('message', 'Email sudah diverifikasi. Silakan masuk menggunakan akun Anda.');

    $record = EmailVerificationCode::query()->where('user_id', $user->id)->first();

    expect($record->is_active)->toBeFalse()
        ->and($record->verified_at)->not->toBeNull();
});

it('invalidates a code after the maximum number of failed attempts', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create();
    $code = issueCodeFor($user);
    $max = EmailVerificationService::MAX_ATTEMPTS;

    for ($i = 0; $i < $max; $i++) {
        $this->postJson('/api/v1/email/verify', ['email' => $user->email, 'code' => '000000'])
            ->assertStatus(422);
    }

    $record = EmailVerificationCode::query()->where('user_id', $user->id)->first();

    expect($record->attempts)->toBe($max)
        ->and($record->is_active)->toBeFalse();

    $this->postJson('/api/v1/email/verify', ['email' => $user->email, 'code' => $code])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('rate limits the verification endpoint', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/v1/email/verify', ['email' => 'budi@example.com', 'code' => '123456'])
            ->assertStatus(422);
    }

    $this->postJson('/api/v1/email/verify', ['email' => 'budi@example.com', 'code' => '123456'])
        ->assertStatus(429);
});

it('resends a code and invalidates the previous one', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create();
    $oldCode = issueCodeFor($user);

    $this->travel(61)->seconds();

    $this->postJson('/api/v1/email/verification/resend', ['email' => $user->email])
        ->assertOk()
        ->assertJsonPath('message', 'Kode verifikasi telah dikirim.');

    $newCode = captureLastCode();

    expect($newCode)->not->toBeNull()
        ->and($newCode)->not->toBe($oldCode);

    $records = EmailVerificationCode::query()->where('user_id', $user->id)->orderBy('id')->get();

    expect($records)->toHaveCount(2)
        ->and($records[0]->is_active)->toBeFalse()
        ->and($records[1]->is_active)->toBeTrue();

    $this->postJson('/api/v1/email/verify', ['email' => $user->email, 'code' => $oldCode])
        ->assertStatus(422);

    $this->postJson('/api/v1/email/verify', ['email' => $user->email, 'code' => $newCode])
        ->assertOk();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('enforces a server-side cooldown before a resend', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create();
    issueCodeFor($user);

    $this->postJson('/api/v1/email/verification/resend', ['email' => $user->email])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    $this->travel(61)->seconds();

    $this->postJson('/api/v1/email/verification/resend', ['email' => $user->email])
        ->assertOk();
});

it('rate limits the resend endpoint', function () {
    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/v1/email/verification/resend', ['email' => 'budi@example.com'])
            ->assertOk();
    }

    $this->postJson('/api/v1/email/verification/resend', ['email' => 'budi@example.com'])
        ->assertStatus(429);
});

it('never returns the verification code through the resend endpoint', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create();
    issueCodeFor($user);

    $this->travel(61)->seconds();

    $response = $this->postJson('/api/v1/email/verification/resend', ['email' => $user->email])
        ->assertOk();

    $code = captureLastCode();

    expect($response->getContent())->not->toContain($code);
});

it('blocks login for an unverified user without issuing a token', function () {
    $user = User::factory()->unverified()->create(['password' => 'secret123']);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Email Anda belum diverifikasi. Silakan masukkan kode verifikasi yang telah dikirim ke email Anda.')
        ->assertJsonPath('verification_required', true)
        ->assertJsonMissing(['token']);
});

it('allows login after the email is verified', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create(['email' => 'veriflogin@example.com', 'password' => 'secret123']);
    $code = issueCodeFor($user);

    $this->postJson('/api/v1/login', ['email' => 'veriflogin@example.com', 'password' => 'secret123'])
        ->assertStatus(403);

    $this->postJson('/api/v1/email/verify', ['email' => 'veriflogin@example.com', 'code' => $code])
        ->assertOk();

    $this->postJson('/api/v1/login', ['email' => 'veriflogin@example.com', 'password' => 'secret123'])
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('records an email_verification entry in the activity log', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create();
    $code = issueCodeFor($user);

    $this->postJson('/api/v1/email/verify', ['email' => $user->email, 'code' => $code])->assertOk();

    $log = ActivityLog::query()->where('action', ActivityLog::ACTION_EMAIL_VERIFICATION)->first();

    expect($log)->not->toBeNull()
        ->and($log->resource_type)->toBe('User')
        ->and($log->user_id)->toBe($user->id);
});
