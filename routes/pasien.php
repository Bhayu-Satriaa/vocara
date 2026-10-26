<?php

use App\Http\Controllers\Pasien\KartuQrController;
use App\Http\Controllers\Pasien\ProfilController;
use Illuminate\Support\Facades\Route;

// Wilayah pasien. Akun pasien hanya boleh masuk lewat sini.
Route::middleware(['auth', 'role:pasien'])->group(function () {
    // Lengkapi Profil: nomor WA wajib, QR dibuat otomatis setelah data tersimpan.
    Route::get('/profil', [ProfilController::class, 'edit'])->name('pasien.profil');
    Route::put('/profil', [ProfilController::class, 'update'])->name('pasien.profil.simpan');

    // Butuh profil lengkap (middleware `profil`).
    Route::get('/kartu-qr', [KartuQrController::class, 'tampil'])
        ->middleware('profil')
        ->name('pasien.qr');

    // Booking janji temu dan riwayat kunjungan pasien ditambahkan di sini.
});
