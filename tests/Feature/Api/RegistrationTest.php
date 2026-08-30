<?php

use App\Mail\VerifyEmailCodeMail;
use App\Models\ActivityLog;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('registers a user as viewer and dispatches a verification email', function () {
    Mail::fake();

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Registrasi berhasil. Silakan verifikasi email Anda dengan kode yang telah dikirim.')
        ->assertJsonStructure(['data' => ['email_masked']]);

    $user = User::query()->where('email', 'budi@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(User::ROLE_VIEWER)
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and(Hash::check('secret123', $user->password))->toBeTrue();

    Mail::assertSent(VerifyEmailCodeMail::class, function (VerifyEmailCodeMail $mail) use ($user) {
        return $mail->user->is($user)
            && strlen($mail->code) === 6
            && ctype_digit($mail->code);
    });

    expect(EmailVerificationCode::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('normalises and trims registration input', function () {
    Mail::fake();

    $this->postJson('/api/v1/register', [
        'name' => '  Budi Santoso  ',
        'email' => '  Budi@Example.COM ',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertStatus(201);

    $user = User::query()->where('email', 'budi@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Budi Santoso');
});

it('rejects an invalid email address', function () {
    $this->postJson('/api/v1/register', [
        'name' => 'Budi Santoso',
        'email' => 'bukan-email',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors(['email']);
});

it('rejects missing registration fields', function () {
    $this->postJson('/api/v1/register', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('rejects a too-short password', function () {
    $this->postJson('/api/v1/register', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('rejects a password that does not match its confirmation', function () {
    $this->postJson('/api/v1/register', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret124',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('never assigns an admin or operator role from the client payload', function () {
    Mail::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Sneaky',
        'email' => 'sneaky@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'admin',
    ])->assertStatus(201);

    expect(User::query()->where('email', 'sneaky@example.com')->first()->role)->toBe(User::ROLE_VIEWER);

    $this->postJson('/api/v1/register', [
        'name' => 'Sneaky Two',
        'email' => 'sneaky2@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'operator',
    ])->assertStatus(201);

    expect(User::query()->where('email', 'sneaky2@example.com')->first()->role)->toBe(User::ROLE_VIEWER);
});

it('returns a generic response without leaking details for an existing verified email', function () {
    Mail::fake();

    User::factory()->create(['email' => 'existing@example.com', 'role' => User::ROLE_ADMIN]);

    $before = User::count();

    $this->postJson('/api/v1/register', [
        'name' => 'Budi Santoso',
        'email' => 'existing@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertStatus(201)
        ->assertJsonPath('success', true);

    expect(User::count())->toBe($before);

    Mail::assertNothingSent();
});

it('re-issues a code for an existing but unverified email without creating a new user', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create(['email' => 'pending@example.com']);

    $before = User::count();

    $this->postJson('/api/v1/register', [
        'name' => 'Budi Santoso',
        'email' => 'pending@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertStatus(201)
        ->assertJsonPath('success', true);

    expect(User::count())->toBe($before);

    Mail::assertSent(VerifyEmailCodeMail::class, fn (VerifyEmailCodeMail $mail) => $mail->user->is($user));
});

it('never returns a token, password or verification code on registration', function () {
    Mail::fake();

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertStatus(201);

    $code = null;
    Mail::assertSent(VerifyEmailCodeMail::class, function (VerifyEmailCodeMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $response->assertJsonMissing(['token'])
        ->assertJsonMissing(['password']);

    expect($response->getContent())->not->toContain($code)
        ->not->toContain('secret123');

    $user = User::query()->where('email', 'budi@example.com')->first();

    expect(EmailVerificationCode::query()->where('user_id', $user->id)->first()->code_hash)
        ->not->toBe($code);
});

it('records a register entry in the activity log', function () {
    Mail::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertStatus(201);

    $log = ActivityLog::query()->where('action', ActivityLog::ACTION_REGISTER)->first();

    expect($log)->not->toBeNull()
        ->and($log->resource_type)->toBe('User')
        ->and(isset($log->new_values['password']))->toBeFalse();
});

it('does not leak passwords into the registration audit log', function () {
    Mail::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertStatus(201);

    expect(ActivityLog::query()->where('resource_type', 'User')->get()
        ->every(fn (ActivityLog $log) => ! isset($log->new_values['password'])))->toBeTrue();
});

it('rate limits the registration endpoint', function () {
    Mail::fake();

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/register', [
            'name' => "Budi $i",
            'email' => "budi$i@example.com",
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(201);
    }

    $this->postJson('/api/v1/register', [
        'name' => 'Budi Terakhir',
        'email' => 'terakhir@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertStatus(429);
});

it('prevents privilege escalation via a registration-created account', function () {
    Mail::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => User::ROLE_ADMIN,
        'can_manage_users' => true,
    ])->assertStatus(201);

    expect(User::query()->where('email', 'budi@example.com')->first()->role)->toBe(User::ROLE_VIEWER);
});
