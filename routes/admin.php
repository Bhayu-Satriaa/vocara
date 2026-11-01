<?php

use App\Http\Controllers\Admin\AkunStafController;
use App\Http\Controllers\Admin\PanelAdminController;
use App\Http\Controllers\Admin\PersetujuanProfilController;
use Illuminate\Support\Facades\Route;

/*
| Wilayah admin.
|
| Route baru admin ditulis di file ini, jangan di routes/web.php.
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin-klinik')->name('admin.')->group(function () {
    // Halaman utama wilayah admin: daftar fitur admin, termasuk akun staf.
    Route::get('/panel-admin', [PanelAdminController::class, 'index'])->name('panel-admin.index');

    // Akun staf (admin, petugas, dokter, apoteker) — pindahan dari panel Filament.
    Route::get('/akun-staf', [AkunStafController::class, 'index'])->name('akun-staf.index');
    Route::get('/akun-staf/tambah', [AkunStafController::class, 'create'])->name('akun-staf.create');
    Route::post('/akun-staf', [AkunStafController::class, 'store'])->name('akun-staf.store');
    Route::get('/akun-staf/{staf}/ubah', [AkunStafController::class, 'edit'])->name('akun-staf.edit');
    Route::put('/akun-staf/{staf}', [AkunStafController::class, 'update'])->name('akun-staf.update');
    Route::post('/akun-staf/{staf}/aktif', [AkunStafController::class, 'toggleAktif'])->name('akun-staf.toggle-aktif');

    // Permohonan persetujuan profil pasien baru (request dari pasien sebelum terbit No RM & QR)
    Route::get('/persetujuan-profil', [PersetujuanProfilController::class, 'index'])->name('persetujuan-profil.index');
    Route::post('/persetujuan-profil/{pengajuan}/approve', [PersetujuanProfilController::class, 'approve'])->name('persetujuan-profil.approve');
    Route::post('/persetujuan-profil/{pengajuan}/reject', [PersetujuanProfilController::class, 'reject'])->name('persetujuan-profil.reject');
});
