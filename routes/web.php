<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EducationDashboardController;
use App\Http\Controllers\HealthDashboardController;
use App\Http\Controllers\HealthFacilityController;
use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\KelurahanController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\PolsekController;
use App\Http\Controllers\PoskamlingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SecurityDashboardController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TipkamtikmasController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pendidikan
    Route::get('/education', [EducationDashboardController::class, 'index'])->name('education.dashboard');
    Route::resource('education/schools', SchoolController::class)->names('education.schools');

    // Ketertiban
    Route::get('/security', [SecurityDashboardController::class, 'index'])->name('security.dashboard');
    Route::resource('security/polseks', PolsekController::class)->names('security.polseks');
    Route::resource('security/tipkamtikmas', TipkamtikmasController::class)->names('security.tipkamtikmas');
    Route::resource('security/poskamlings', PoskamlingController::class)->names('security.poskamlings');
    Route::resource('security/markets', MarketController::class)->names('security.markets');

    // Kesehatan
    Route::get('/health', [HealthDashboardController::class, 'index'])->name('health.dashboard');
    Route::resource('health/facilities', HealthFacilityController::class)
        ->names('health.facilities')
        ->parameters(['facilities' => 'health_facility']);

    // Peta Gabungan
    Route::get('/maps', [MapController::class, 'index'])->name('maps.index');

    // Data Master
    Route::resource('master/kecamatans', KecamatanController::class)->names('master.kecamatans');
    Route::resource('master/kelurahan', KelurahanController::class)->names('master.kelurahans');
    Route::resource('master/subjects', SubjectController::class)->names('master.subjects');

    // JSON helpers
    Route::get('/api/kelurahans/by-kecamatan', [KelurahanController::class, 'byKecamatan'])->name('api.kelurahans.by-kecamatan');

    // Users (admin only via policy)
    Route::resource('users', UserController::class)->names('users');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
