<?php

use App\Http\Controllers\Petugas\PemindaianQrController;
use Illuminate\Support\Facades\Route;

// Wilayah petugas pendaftaran / kasir (admin boleh ikut memantau).
Route::middleware(['auth', 'role:petugas,admin'])->prefix('petugas')->name('petugas.')->group(function () {
    // Scan kartu QR pasien: cari data, catat scan, verifikasi KTP.
    Route::get('/scan', [PemindaianQrController::class, 'form'])->name('scan');
    Route::post('/scan', [PemindaianQrController::class, 'cari'])->name('scan.cari');
    Route::get('/scan/{pasien}', [PemindaianQrController::class, 'hasil'])->name('scan.hasil');
    Route::post('/scan/{pasien}/verifikasi', [PemindaianQrController::class, 'verifikasi'])->name('scan.verifikasi');

    // Antrean, check-in kunjungan, dan kasir ditambahkan di sini.
});
