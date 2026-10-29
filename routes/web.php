<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
| Route bersama (semua role).
|
| Route per wilayah kerja ada di file terpisah supaya beberapa orang bisa bekerja
| tanpa berebut file yang sama:
|   routes/auth.php      — login/logout staf dan pasien
|   routes/pasien.php    — wilayah pasien
|   routes/petugas.php   — wilayah petugas pendaftaran/kasir
|   routes/dokter.php    — wilayah dokter
|   routes/apoteker.php  — wilayah apoteker
|   routes/admin.php     — wilayah admin (panel Filament didaftarkan di
|                          AdminPanelProvider, bukan di sini)
|
| Keenam file itu didaftarkan di bootstrap/app.php.
*/

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
