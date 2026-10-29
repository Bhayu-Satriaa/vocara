<?php

use Illuminate\Support\Facades\Route;

/*
| Wilayah admin.
|
| Belum ada route: data master (poli, dokter, jadwal, tarif, obat) dan laporan
| menyusul. Panel Filament di /admin didaftarkan lewat AdminPanelProvider, bukan
| di file ini.
|
| Route baru admin ditulis di file ini, jangan di routes/web.php.
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin-klinik')->name('admin.')->group(function () {
    // Route data master dan laporan menyusul di sini (hindari bentrok dengan /admin).
});
