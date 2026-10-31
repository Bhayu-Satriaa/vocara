<?php

use App\Http\Controllers\Admin\PersetujuanProfilController;
use Illuminate\Support\Facades\Route;

/*
| Wilayah admin.
|
| Route baru admin ditulis di file ini, jangan di routes/web.php.
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin-klinik')->name('admin.')->group(function () {
    // Permohonan persetujuan profil pasien baru (request dari pasien sebelum terbit No RM & QR)
    Route::get('/persetujuan-profil', [PersetujuanProfilController::class, 'index'])->name('persetujuan-profil.index');
    Route::post('/persetujuan-profil/{pengajuan}/approve', [PersetujuanProfilController::class, 'approve'])->name('persetujuan-profil.approve');
    Route::post('/persetujuan-profil/{pengajuan}/reject', [PersetujuanProfilController::class, 'reject'])->name('persetujuan-profil.reject');
});
