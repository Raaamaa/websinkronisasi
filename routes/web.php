<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\ForceChangePasswordController;
use App\Http\Controllers\Auth\SSOController;
use App\Http\Controllers\Evidence\EvidenceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Jalur Autentikasi SSO BPS (AUTH-01, AUTH-02)
Route::prefix('auth/sso')->name('auth.sso.')->group(function (): void {
    Route::get('/login', [SSOController::class, 'login'])->name('login');
    Route::get('/callback', [SSOController::class, 'callback'])->name('callback');
    Route::get('/mock-login', [SSOController::class, 'mockLogin'])->name('mock_login');
});

// Jalur Karantina Paksa Ganti Kata Sandi (AUTH-03, AUTH-04, D6, AC-03)
Route::middleware(['web', 'auth'])->prefix('auth/password')->name('auth.password.')->group(function (): void {
    Route::get('/change', [ForceChangePasswordController::class, 'show'])->name('change');
    Route::post('/change', [ForceChangePasswordController::class, 'update'])->name('change.update');
});

// Jalur Proteksi & Pengujian Middleware (AC-01, AC-15)
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/test-protected-route', fn () => response('OK', 200));
    Route::post('/test-admin-only-action', fn () => response('OK', 200))->middleware('permission:evidence.upload');
});

// Jalur Bukti Dukung (BD-01, BD-09, BD-10)
Route::middleware(['web', 'auth'])->prefix('evidence')->name('evidence.')->group(function (): void {
    Route::post('/upload', [EvidenceController::class, 'upload'])->name('upload');
    Route::get('/download/{id}', [EvidenceController::class, 'download'])->name('download');
    Route::delete('/{id}', [EvidenceController::class, 'destroy'])->name('destroy');
});
