<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\LkController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/search');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', fn () => redirect()->route('search'))->name('dashboard');
    Route::view('/search', 'search.index')->name('search');

    Route::middleware('role:admin,super_admin')->prefix('manage')->name('manage.')->group(function (): void {
        Route::get('/evidence', [EvidenceController::class, 'index'])->name('evidence.index');
        Route::post('/evidence', [EvidenceController::class, 'store'])->name('evidence.store');
        Route::delete('/evidence/{evidence}', [EvidenceController::class, 'destroy'])->name('evidence.destroy');
    });

    Route::middleware('role:super_admin')->prefix('manage')->name('manage.')->group(function (): void {
        Route::get('/lks', [LkController::class, 'index'])->name('lks.index');
        Route::post('/lks', [LkController::class, 'store'])->name('lks.store');
        Route::delete('/lks/{lk}', [LkController::class, 'destroy'])->name('lks.destroy');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    });
});
