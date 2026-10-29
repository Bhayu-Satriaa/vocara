<?php

use Illuminate\Support\Facades\Route;

/*
| Wilayah apoteker.
|
| Belum ada route: modul farmasi (daftar resep, status resep, serah obat lewat QR,
| stok obat, cetak etiket) belum dibuat.
|
| Route baru apoteker ditulis di file ini, jangan di routes/web.php.
*/

Route::middleware(['auth', 'role:apoteker'])->prefix('apoteker')->name('apoteker.')->group(function () {
    // Route modul farmasi menyusul di sini.
});
