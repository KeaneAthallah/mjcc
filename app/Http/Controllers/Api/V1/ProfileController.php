<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        return ApiResponse::success(new UserResource(request()->user()));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->only(['name', 'email']));

        return ApiResponse::success(new UserResource($request->user()), 'Profil berhasil diperbarui.');
    }

    public function updatePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Kata sandi saat ini tidak cocok.'],
            ]);
        }

        $user->update(['password' => $request->password]);

        return ApiResponse::success(null, 'Kata sandi berhasil diperbarui.');
    }
}
