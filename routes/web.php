<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Pasien\KartuQrController;
use App\Http\Controllers\Pasien\ProfilController;
use App\Http\Controllers\Petugas\PemindaianQrController;
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
    Route::middleware('role:petugas,admin')->prefix('petugas')->name('petugas.')->group(function () {
        // Scan kartu QR pasien: cari data, catat scan, verifikasi KTP.
        Route::get('/scan', [PemindaianQrController::class, 'form'])->name('scan');
        Route::post('/scan', [PemindaianQrController::class, 'cari'])->name('scan.cari');
        Route::get('/scan/{pasien}', [PemindaianQrController::class, 'hasil'])->name('scan.hasil');
        Route::post('/scan/{pasien}/verifikasi', [PemindaianQrController::class, 'verifikasi'])->name('scan.verifikasi');
    });
});

// Panel admin (Filament) ada di /admin; resource akun staf dikelola di sana.
