<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLog) {}

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
}
