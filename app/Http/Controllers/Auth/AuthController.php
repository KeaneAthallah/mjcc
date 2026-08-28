<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $this->activityLog->record(
                action: ActivityLog::ACTION_LOGIN,
                user: $request->user(),
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );

            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => 'Kredensial yang dimasukkan tidak cocok dengan data kami.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->activityLog->record(
            action: ActivityLog::ACTION_LOGOUT,
            user: $request->user(),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
