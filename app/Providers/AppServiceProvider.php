<?php

namespace App\Providers;

use App\Models\HealthFacility;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\SosAlert;
use App\Models\Tipkamtikmas;
use App\Support\Access;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(Str::transliterate(
                Str::lower((string) $request->string('email')).'|'.$request->ip()
            ));
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('email_verify', function (Request $request) {
            return Limit::perMinute(10)->by(Str::transliterate(
                Str::lower((string) $request->string('email')).'|'.$request->ip()
            ));
        });

        RateLimiter::for('email_resend', function (Request $request) {
            return Limit::perMinute(3)->by(Str::transliterate(
                Str::lower((string) $request->string('email')).'|'.$request->ip()
            ));
        });

        RateLimiter::for('sos', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?? $request->ip());
        });

        Model::preventLazyLoading(! $this->app->isProduction());

        Blade::if('canwrite', fn ($resource) => Access::canWrite(auth()->user(), $resource));

        View::composer('*', function ($view) {
            $view->with('currentUser', auth()->user());

            $view->with('flashMessages', collect([
                ['type' => 'success', 'message' => session('success')],
                ['type' => 'error', 'message' => session('error')],
                ['type' => 'warning', 'message' => session('warning')],
                ['type' => 'info', 'message' => session('info')],
            ])->filter(fn ($flash) => $flash['message'])->values()->all());
        });

        View::composer('layouts.app', function ($view) {
            $view->with('sidebarStats', [
                'pendidikan' => (string) School::count(),
                'ketertiban' => (string) (
                    Polsek::count()
                    + Poskamling::count()
                    + Market::count()
                    + Tipkamtikmas::count()
                    + Kelurahan::count()
                ),
                'kesehatan' => (string) HealthFacility::count(),
            ]);

            $user = auth()->user();

            $view->with('sosStats', $user !== null && $user->canManageData()
                ? [
                    'open' => SosAlert::query()->whereIn('status', SosAlert::openStatuses())->count(),
                    'active' => SosAlert::query()->where('status', SosAlert::STATUS_ACTIVE)->count(),
                ]
                : [
                    'open' => 0,
                    'active' => 0,
                ]);

            $view->with('currentUser', auth()->user());
        });
    }
}
