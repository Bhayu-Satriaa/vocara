<?php

use App\Http\Controllers\Petugas\PasienController;
use App\Http\Controllers\Petugas\PemindaianQrController;
use Illuminate\Support\Facades\Route;

// Wilayah petugas pendaftaran / kasir (admin boleh ikut memantau).
Route::middleware(['auth', 'role:petugas,admin'])->prefix('petugas')->name('petugas.')->group(function () {
    // Pendaftaran Pasien Baru (Walk-in)
    Route::get('/pasien/baru', [PasienController::class, 'create'])->name('pasien.create');
    Route::post('/pasien', [PasienController::class, 'store'])->name('pasien.store');
    Route::get('/pasien/{pasien}', [PasienController::class, 'show'])->name('pasien.show');
    Route::get('/pasien/{pasien}/kartu', [PasienController::class, 'kartu'])->name('pasien.kartu');

    // Scan kartu QR pasien: cari data, catat scan, verifikasi KTP, dan check-in antrean.
    Route::get('/scan', [PemindaianQrController::class, 'form'])->name('scan');
    Route::post('/scan', [PemindaianQrController::class, 'cari'])->name('scan.cari');
    Route::get('/scan/{pasien}', [PemindaianQrController::class, 'hasil'])->name('scan.hasil');
    Route::post('/scan/{pasien}/verifikasi', [PemindaianQrController::class, 'verifikasi'])->name('scan.verifikasi');
    Route::post('/scan/{pasien}/checkin', [PemindaianQrController::class, 'checkin'])->name('scan.checkin');

    // Antrean, check-in kunjungan, dan kasir ditambahkan di sini.
});
