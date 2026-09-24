<?php

use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommandAlertController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\HealthFacilityController;
use App\Http\Controllers\Api\V1\KecamatanController;
use App\Http\Controllers\Api\V1\KelurahanController;
use App\Http\Controllers\Api\V1\MapController;
use App\Http\Controllers\Api\V1\MarketController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PolsekController;
use App\Http\Controllers\Api\V1\PoskamlingController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PublicController;
use App\Http\Controllers\Api\V1\PublicDataApiController;
use App\Http\Controllers\Api\V1\SchoolController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SosController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\TipkamtikmasController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function () {
    // Public authentication
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('email/verify', [AuthController::class, 'verifyEmail'])->middleware('throttle:email_verify');
    Route::post('email/verification/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:email_resend');

    // Public "Data Publik" (open read endpoints, no token required)
    Route::prefix('public')->name('public.')->group(function () {
        Route::get('overview', [PublicController::class, 'overview']);
        Route::get('schools', [PublicController::class, 'schools']);
        Route::get('schools/{id}', [PublicController::class, 'school']);
        Route::get('health-facilities', [PublicController::class, 'healthFacilities']);
        Route::get('health-facilities/{id}', [PublicController::class, 'healthFacility']);
        Route::get('polseks', [PublicController::class, 'polseks']);
        Route::get('polseks/{id}', [PublicController::class, 'polsek']);
        Route::get('poskamlings', [PublicController::class, 'poskamlings']);
        Route::get('poskamlings/{id}', [PublicController::class, 'poskamling']);
        Route::get('tipkamtikmas', [PublicController::class, 'tipkamtikmas']);
        Route::get('tipkamtikmas/{id}', [PublicController::class, 'tipkamtikmasShow']);
        Route::get('markets', [PublicController::class, 'markets']);
        Route::get('markets/{id}', [PublicController::class, 'market']);
        Route::get('kelurahans', [PublicController::class, 'kelurahans']);
        Route::get('kelurahans/{id}', [PublicController::class, 'kelurahan']);
    });

    // Public "Data Publik Multi-Source" (open read endpoints, no token required)
    Route::prefix('public-data')->name('public-data.')->group(function () {
        Route::get('/categories', [PublicDataApiController::class, 'categories'])->name('categories');
        Route::get('/sources', [PublicDataApiController::class, 'sources'])->name('sources');
        Route::get('/sources/{source}', [PublicDataApiController::class, 'show'])->name('source');
    });

    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        // Profile
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::put('profile/password', [ProfileController::class, 'updatePassword']);

        // Dashboards
        Route::get('dashboard', [DashboardController::class, 'overview']);
        Route::get('dashboard/education', [DashboardController::class, 'education']);
        Route::get('dashboard/security', [DashboardController::class, 'security']);
        Route::get('dashboard/health', [DashboardController::class, 'health']);

        // Map
        Route::get('maps', [MapController::class, 'index']);

        // Master data
        Route::get('kecamatans/trash', [KecamatanController::class, 'trash']);
        Route::get('kecamatans/{kecamatan}/kelurahans', [KecamatanController::class, 'kelurahans']);
        Route::apiResource('kecamatans', KecamatanController::class);
        Route::put('kecamatans/{id}/restore', [KecamatanController::class, 'restore']);
        Route::delete('kecamatans/{id}/force', [KecamatanController::class, 'forceDestroy']);

        Route::get('kelurahans/trash', [KelurahanController::class, 'trash']);
        Route::apiResource('kelurahans', KelurahanController::class);
        Route::put('kelurahans/{id}/restore', [KelurahanController::class, 'restore']);
        Route::delete('kelurahans/{id}/force', [KelurahanController::class, 'forceDestroy']);

        Route::apiResource('subjects', SubjectController::class);

        // Education (Sekolah)
        Route::get('schools/trash', [SchoolController::class, 'trash']);
        Route::apiResource('schools', SchoolController::class);
        Route::put('schools/{id}/restore', [SchoolController::class, 'restore']);
        Route::delete('schools/{id}/force', [SchoolController::class, 'forceDestroy']);

        // Security (Ketertiban)
        Route::apiResource('polseks', PolsekController::class);

        Route::get('tipkamtikmas/trash', [TipkamtikmasController::class, 'trash']);
        Route::apiResource('tipkamtikmas', TipkamtikmasController::class);
        Route::put('tipkamtikmas/{id}/restore', [TipkamtikmasController::class, 'restore']);
        Route::delete('tipkamtikmas/{id}/force', [TipkamtikmasController::class, 'forceDestroy']);

        Route::get('poskamlings/trash', [PoskamlingController::class, 'trash']);
        Route::apiResource('poskamlings', PoskamlingController::class);
        Route::put('poskamlings/{id}/restore', [PoskamlingController::class, 'restore']);
        Route::delete('poskamlings/{id}/force', [PoskamlingController::class, 'forceDestroy']);

        Route::get('markets/trash', [MarketController::class, 'trash']);
        Route::apiResource('markets', MarketController::class);
        Route::put('markets/{id}/restore', [MarketController::class, 'restore']);
        Route::delete('markets/{id}/force', [MarketController::class, 'forceDestroy']);

        // Health (Kesehatan)
        Route::get('health/facilities/trash', [HealthFacilityController::class, 'trash']);
        Route::apiResource('health/facilities', HealthFacilityController::class)
            ->parameters(['facilities' => 'health_facility']);
        Route::put('health/facilities/{id}/restore', [HealthFacilityController::class, 'restore']);
        Route::delete('health/facilities/{id}/force', [HealthFacilityController::class, 'forceDestroy']);

        // Users (admin only via UserPolicy)
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/verify-email', [UserController::class, 'verifyEmail']);

        // Audit log (admin only via ActivityLogPolicy)
        Route::get('audit', [AuditController::class, 'index']);

        // SOS / Emergency (any authenticated user may create; management via policy)
        Route::get('sos/active-count', [SosController::class, 'activeCount']);
        Route::get('sos/my-open', [SosController::class, 'myOpen']);
        Route::get('sos/active-incidents', [SosController::class, 'activeIncidents']);
        Route::post('sos', [SosController::class, 'store'])->middleware('throttle:sos');
        Route::get('sos', [SosController::class, 'index']);
        Route::post('sos/{sos}/accept', [SosController::class, 'accept']);
        Route::post('sos/{sos}/on-the-way', [SosController::class, 'onTheWay']);
        Route::post('sos/{sos}/arrived', [SosController::class, 'arrived']);
        Route::post('sos/{sos}/constraint', [SosController::class, 'constrain']);
        Route::post('sos/{sos}/location', [SosController::class, 'updateLocation']);
        Route::get('sos/{sos}/responder-locations', [SosController::class, 'responderLocations']);
        Route::get('sos/{sos}', [SosController::class, 'show']);
        Route::post('sos/{sos}/acknowledge', [SosController::class, 'acknowledge']);
        Route::post('sos/{sos}/respond', [SosController::class, 'respond']);
        Route::post('sos/{sos}/resolve', [SosController::class, 'resolve']);
        Route::post('sos/{sos}/cancel', [SosController::class, 'cancel']);

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);

        // Command center alerts + keystone search (read-only, for the SPA)
        Route::get('alerts', [CommandAlertController::class, 'index']);
        Route::get('search', [SearchController::class, 'index']);
    });
});
