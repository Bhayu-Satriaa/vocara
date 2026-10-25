<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Halaman pasien belum dibuat; sementara root diarahkan ke dashboard staf.
Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'form'])->name('login');
    Route::post('/login', [LoginController::class, 'masuk'])->name('login.masuk');
});

Route::post('/logout', [LoginController::class, 'keluar'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Panel admin: kelola akun staf (pasien mendaftar sendiri lewat Google).
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/pengguna', [UserController::class, 'index'])->name('pengguna');
        Route::get('/pengguna/baru', [UserController::class, 'create'])->name('pengguna.baru');
        Route::post('/pengguna', [UserController::class, 'store'])->name('pengguna.simpan');
    });
