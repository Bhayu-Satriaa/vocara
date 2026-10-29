<?php

use Illuminate\Support\Facades\Route;

/*
| Wilayah dokter.
|
| Belum ada route: modul pemeriksaan (daftar pasien hari ini, rekam medis lewat QR,
| catatan SOAP + tanda vital, diagnosis ICD-10, resep) belum dibuat.
|
| Route baru dokter ditulis di file ini, jangan di routes/web.php.
*/

Route::middleware(['auth', 'role:dokter'])->prefix('dokter')->name('dokter.')->group(function () {
    // Route modul pemeriksaan menyusul di sini.
});
