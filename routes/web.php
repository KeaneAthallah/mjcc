<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CommandAlertController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataImportController;
use App\Http\Controllers\EducationDashboardController;
use App\Http\Controllers\HealthDashboardController;
use App\Http\Controllers\HealthFacilityController;
use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\KecamatanIntelligenceController;
use App\Http\Controllers\KelurahanController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\PolsekController;
use App\Http\Controllers\PoskamlingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicDataController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SecurityDashboardController;
use App\Http\Controllers\SosController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TipkamtikmasController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt')->middleware(['guest', 'throttle:login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');

    // Command Center Alerts (persisted operational alerts)
    Route::get('/alerts/summary', [CommandAlertController::class, 'summary'])->name('alerts.summary');
    Route::get('/alerts', [CommandAlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/{alert}/status', [CommandAlertController::class, 'updateStatus'])->name('alerts.status');
    Route::get('/alerts/{alert}', [CommandAlertController::class, 'show'])->name('alerts.show');

    // Kecamatan Intelligence (rangking + profil)
    Route::get('/kecamatan', [KecamatanIntelligenceController::class, 'index'])->name('kecamatan.overview');
    Route::get('/kecamatan/{kecamatan}', [KecamatanIntelligenceController::class, 'show'])->name('kecamatan.show');

    // Keystone Search
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    // Pendidikan
    Route::get('/education', [EducationDashboardController::class, 'index'])->name('education.dashboard');
    Route::get('education/schools/trash', [SchoolController::class, 'trash'])->name('education.schools.trash');
    Route::resource('education/schools', SchoolController::class)->names('education.schools');
    Route::put('education/schools/{id}/restore', [SchoolController::class, 'restore'])->name('education.schools.restore');
    Route::delete('education/schools/{id}/force', [SchoolController::class, 'forceDestroy'])->name('education.schools.force-destroy');

    // Ketertiban
    Route::get('/security', [SecurityDashboardController::class, 'index'])->name('security.dashboard');
    Route::resource('security/polseks', PolsekController::class)->names('security.polseks');
    Route::get('security/tipkamtikmas/trash', [TipkamtikmasController::class, 'trash'])->name('security.tipkamtikmas.trash');
    Route::resource('security/tipkamtikmas', TipkamtikmasController::class)->names('security.tipkamtikmas');
    Route::put('security/tipkamtikmas/{id}/restore', [TipkamtikmasController::class, 'restore'])->name('security.tipkamtikmas.restore');
    Route::delete('security/tipkamtikmas/{id}/force', [TipkamtikmasController::class, 'forceDestroy'])->name('security.tipkamtikmas.force-destroy');
    Route::get('security/poskamlings/trash', [PoskamlingController::class, 'trash'])->name('security.poskamlings.trash');
    Route::resource('security/poskamlings', PoskamlingController::class)->names('security.poskamlings');
    Route::put('security/poskamlings/{id}/restore', [PoskamlingController::class, 'restore'])->name('security.poskamlings.restore');
    Route::delete('security/poskamlings/{id}/force', [PoskamlingController::class, 'forceDestroy'])->name('security.poskamlings.force-destroy');
    Route::get('security/markets/trash', [MarketController::class, 'trash'])->name('security.markets.trash');
    Route::resource('security/markets', MarketController::class)->names('security.markets');
    Route::put('security/markets/{id}/restore', [MarketController::class, 'restore'])->name('security.markets.restore');
    Route::delete('security/markets/{id}/force', [MarketController::class, 'forceDestroy'])->name('security.markets.force-destroy');

    // Kesehatan
    Route::get('/health', [HealthDashboardController::class, 'index'])->name('health.dashboard');
    Route::get('health/facilities/trash', [HealthFacilityController::class, 'trash'])->name('health.facilities.trash');
    Route::resource('health/facilities', HealthFacilityController::class)
        ->names('health.facilities')
        ->parameters(['facilities' => 'health_facility']);
    Route::put('health/facilities/{id}/restore', [HealthFacilityController::class, 'restore'])->name('health.facilities.restore');
    Route::delete('health/facilities/{id}/force', [HealthFacilityController::class, 'forceDestroy'])->name('health.facilities.force-destroy');

    // Peta Gabungan
    Route::get('/maps', [MapController::class, 'index'])->name('maps.index');
    Route::get('/maps/data', [MapController::class, 'data'])->name('maps.data');

    // Data Publik (Satu Data Morowali)
    Route::get('/data-publik', [PublicDataController::class, 'index'])->name('public-data.index');
    Route::get('/data-publik/{sector}', [PublicDataController::class, 'show'])
        ->whereIn('sector', ['pendidikan', 'kesehatan', 'keamanan'])
        ->name('public-data.show');
    Route::get('/data-publik/{sector}/dataset/{dataset}', [PublicDataController::class, 'dataset'])
        ->whereIn('sector', ['pendidikan', 'kesehatan', 'keamanan'])
        ->name('public-data.dataset');

    // Sinkronisasi Data Publik → Data Master (admin + operator via policy)
    Route::get('/sinkronisasi', [DataImportController::class, 'index'])->name('data-import.index');
    Route::post('/sinkronisasi/run', [DataImportController::class, 'run'])->name('data-import.run');

    // Data Master
    Route::get('master/kecamatans/trash', [KecamatanController::class, 'trash'])->name('master.kecamatans.trash');
    Route::resource('master/kecamatans', KecamatanController::class)->names('master.kecamatans');
    Route::put('master/kecamatans/{id}/restore', [KecamatanController::class, 'restore'])->name('master.kecamatans.restore');
    Route::delete('master/kecamatans/{id}/force', [KecamatanController::class, 'forceDestroy'])->name('master.kecamatans.force-destroy');
    Route::get('master/kelurahan/trash', [KelurahanController::class, 'trash'])->name('master.kelurahans.trash');
    Route::resource('master/kelurahan', KelurahanController::class)->names('master.kelurahans');
    Route::put('master/kelurahan/{id}/restore', [KelurahanController::class, 'restore'])->name('master.kelurahans.restore');
    Route::delete('master/kelurahan/{id}/force', [KelurahanController::class, 'forceDestroy'])->name('master.kelurahans.force-destroy');
    Route::resource('master/subjects', SubjectController::class)->names('master.subjects');

    // JSON helpers
    Route::get('/api/kelurahans/by-kecamatan', [KelurahanController::class, 'byKecamatan'])->name('api.kelurahans.by-kecamatan');

    // Users (admin only via policy)
    Route::resource('users', UserController::class)->names('users');

    // Audit log (admin only via policy)
    Route::get('/audit', [ActivityLogController::class, 'index'])->name('audit.index');

    // SOS / Emergency (any user creates; operator/admin manages via policy)
    Route::get('/sos/active-count', [SosController::class, 'activeCount'])->name('sos.active-count');
    Route::get('/sos/live', [SosController::class, 'live'])->name('sos.live');
    Route::get('/sos/live/{sos}', [SosController::class, 'liveShow'])->name('sos.live-show');
    Route::get('/sos', [SosController::class, 'index'])->name('sos.index');
    Route::get('/sos/{sos}', [SosController::class, 'show'])->name('sos.show');
    Route::post('/sos/{sos}/acknowledge', [SosController::class, 'acknowledge'])->name('sos.acknowledge');
    Route::post('/sos/{sos}/respond', [SosController::class, 'respond'])->name('sos.respond');
    Route::post('/sos/{sos}/resolve', [SosController::class, 'resolve'])->name('sos.resolve');
    Route::post('/sos/{sos}/cancel', [SosController::class, 'cancel'])->name('sos.cancel');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
