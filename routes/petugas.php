<?php

use App\Http\Controllers\Petugas\AntreanController;
use App\Http\Controllers\Petugas\PasienController;
use App\Http\Controllers\Petugas\PemindaianQrController;
use Illuminate\Support\Facades\Route;

// Wilayah petugas pendaftaran / kasir (admin boleh ikut memantau).
Route::middleware(['auth', 'role:petugas,admin'])->prefix('dashboard')->name('petugas.')->group(function () {
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

    // Manajemen Antrean & Konfirmasi Booking (Tugas 4)
    Route::get('/antrean', [AntreanController::class, 'index'])->name('antrean.index');
    Route::post('/antrean/{visit}/panggil', [AntreanController::class, 'panggil'])->name('antrean.panggil');
    Route::post('/antrean/{visit}/status', [AntreanController::class, 'updateStatus'])->name('antrean.status');
    Route::post('/antrean/{visit}/batal', [AntreanController::class, 'batal'])->name('antrean.batal');
    Route::post('/antrean/{visit}/konfirmasi', [AntreanController::class, 'konfirmasiBooking'])->name('antrean.konfirmasi');
});
