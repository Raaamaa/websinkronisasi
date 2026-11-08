<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\ForceChangePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SSOController;
use App\Http\Controllers\Evidence\EvidenceController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Halaman Utama: Redirect ke Dashboard jika sudah login, atau tampilkan Login jika tamu
Route::get('/', function () {
    return Auth::check() ? redirect('/admin') : view('auth.login');
});

// Jalur Login Mandiri Modern ala Web GUCI
Route::middleware('web')->group(function (): void {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/admin/login', fn () => redirect()->route('login'))->name('filament.admin.auth.login');
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

// Jalur Proteksi & Pengujian Middleware (AC-01, AC-15) - Khusus Lingkungan Dev/Test
if (app()->environment(['local', 'testing'])) {
    Route::middleware(['web', 'auth'])->group(function (): void {
        Route::get('/test-protected-route', fn () => response('OK', 200));
        Route::post('/test-admin-only-action', fn () => response('OK', 200))->middleware('permission:lk.manage_drive_links');
    });
}

// Jalur Bukti Dukung (BD-01, BD-09, BD-10)
Route::middleware(['web', 'auth'])->prefix('evidence')->name('evidence.')->group(function (): void {
    Route::post('/upload', [EvidenceController::class, 'upload'])->name('upload');
    Route::get('/download/{id}', [EvidenceController::class, 'download'])->name('download');
    Route::delete('/{id}', [EvidenceController::class, 'destroy'])->name('destroy');
});
