<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\ResendVerificationRequest;
use App\Http\Requests\Api\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const MESSAGE_VERIFICATION_REQUIRED = 'Email Anda belum diverifikasi. Silakan masukkan kode verifikasi yang telah dikirim ke email Anda.';

    private const MESSAGE_REGISTERED = 'Registrasi berhasil. Silakan verifikasi email Anda dengan kode yang telah dikirim.';

    private const MESSAGE_CODE_SENT = 'Kode verifikasi telah dikirim.';

    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly EmailVerificationService $verification,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang dimasukkan tidak cocok dengan data kami.'],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return ApiResponse::error(self::MESSAGE_VERIFICATION_REQUIRED, 403, [], [
                'verification_required' => true,
            ]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        $this->activityLog->record(
            action: ActivityLog::ACTION_LOGIN,
            user: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return ApiResponse::success([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Login berhasil');
    }

    /**
     * Create a public account with the `viewer` role and dispatch a
     * verification email. The role is decided by the backend and can never be
     * influenced by the client payload.
     *
     * To avoid account enumeration, the same generic envelope is returned for
     * fresh registrations and for emails that already exist (verified accounts
     * simply do not receive another email).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $existing = User::query()->where('email', $data['email'])->first();

        if ($existing) {
            if (! $existing->hasVerifiedEmail()) {
                $this->verification->send($existing);
            }

            return ApiResponse::success($this->registrationData($existing), self::MESSAGE_REGISTERED, 201);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => User::ROLE_VIEWER,
        ]);

        $this->activityLog->record(
            action: ActivityLog::ACTION_REGISTER,
            resourceType: 'User',
            resourceId: $user->id,
            user: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        $this->verification->send($user);

        return ApiResponse::success($this->registrationData($user), self::MESSAGE_REGISTERED, 201);
    }

    /**
     * Verify a submitted six-digit code. Failures are reported with a generic
     * message so the endpoint cannot be used to probe for registered emails.
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => ['Kode verifikasi tidak valid.'],
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success(null, 'Email sudah diverifikasi. Silakan masuk menggunakan akun Anda.');
        }

        $this->verification->verify($user, $request->code);

        $this->activityLog->record(
            action: ActivityLog::ACTION_EMAIL_VERIFICATION,
            resourceType: 'User',
            resourceId: $user->id,
            user: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return ApiResponse::success(null, 'Email berhasil diverifikasi. Silakan masuk menggunakan akun Anda.');
    }

    /**
     * Resend the verification code. The response is intentionally identical
     * for known and unknown addresses.
     */
    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->email)->first();

        if (! $user || $user->hasVerifiedEmail()) {
            return ApiResponse::success(null, self::MESSAGE_CODE_SENT);
        }

        $this->verification->resend($user);

        return ApiResponse::success(null, self::MESSAGE_CODE_SENT);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->activityLog->record(
            action: ActivityLog::ACTION_LOGOUT,
            user: $request->user(),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logout berhasil');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()));
    }

    /**
     * @return array<string, string>
     */
    private function registrationData(User $user): array
    {
        return [
            'email_masked' => $this->maskEmail($user->email),
        ];
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        return substr($local, 0, 1).'***@'.$domain;
    }
}
