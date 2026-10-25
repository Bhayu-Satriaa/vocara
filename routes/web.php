<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Pasien\KartuQrController;
use App\Http\Controllers\Pasien\ProfilController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    // Staf: email + password (akun dibuat admin).
    Route::get('/login', [LoginController::class, 'form'])->name('login');
    Route::post('/login', [LoginController::class, 'masuk'])->name('login.masuk');

    // Pasien: Google OAuth.
    Route::get('/auth/google', [GoogleController::class, 'arahkan'])->name('google.arahkan');
    Route::get('/auth/google/callback', [GoogleController::class, 'panggilBalik'])->name('google.balik');
});

Route::post('/logout', [LoginController::class, 'keluar'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:pasien')->group(function () {
        // Lengkapi Profil: nomor WA wajib, QR dibuat otomatis setelah data tersimpan.
        Route::get('/profil', [ProfilController::class, 'edit'])->name('pasien.profil');
        Route::put('/profil', [ProfilController::class, 'update'])->name('pasien.profil.simpan');

        // Butuh profil lengkap (middleware `profil`).
        Route::get('/kartu-qr', [KartuQrController::class, 'tampil'])
            ->middleware('profil')
            ->name('pasien.qr');
    });
});

// Panel admin (Filament) ada di /admin; resource akun staf dikelola di sana.
