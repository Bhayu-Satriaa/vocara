<?php

use App\Http\Controllers\Admin\AkunStafController;
use App\Http\Controllers\Admin\JadwalDokterController;
use App\Http\Controllers\Admin\PasienController;
use App\Http\Controllers\Admin\PengetahuanChatbotController;
use App\Http\Controllers\Admin\PersetujuanProfilController;
use Illuminate\Support\Facades\Route;

/*
| Wilayah admin.
|
| Semua halaman admin dilayani dari /dashboard (tanpa panel Filament).
| Route baru admin ditulis di file ini, jangan di routes/web.php.
*/

Route::middleware(['auth', 'role:admin'])->prefix('dashboard')->name('admin.')->group(function () {
    // Jadwal dokter: daftar dokter, lalu atur hari praktik per dokter.
    Route::get('/jadwal-dokter', [JadwalDokterController::class, 'index'])->name('jadwal-dokter.index');
    Route::get('/jadwal-dokter/{dokter}', [JadwalDokterController::class, 'show'])->name('jadwal-dokter.show');
    Route::post('/jadwal-dokter/{dokter}', [JadwalDokterController::class, 'store'])->name('jadwal-dokter.store');
    Route::put('/jadwal-dokter/{dokter}/jadwal/{jadwal}', [JadwalDokterController::class, 'update'])->name('jadwal-dokter.update');
    Route::delete('/jadwal-dokter/{dokter}/jadwal/{jadwal}', [JadwalDokterController::class, 'destroy'])->name('jadwal-dokter.destroy');

    // Data master pasien: daftar + pencarian + detail data diri.
    Route::get('/pasien', [PasienController::class, 'index'])->name('pasien.index');
    Route::get('/pasien/{pasien}/detail', [PasienController::class, 'show'])->name('pasien.show');

    // Permohonan persetujuan profil pasien baru (request dari pasien sebelum terbit No RM & QR)
    Route::get('/persetujuan-profil', [PersetujuanProfilController::class, 'index'])->name('persetujuan-profil.index');
    Route::post('/persetujuan-profil/{pengajuan}/approve', [PersetujuanProfilController::class, 'approve'])->name('persetujuan-profil.approve');
    Route::post('/persetujuan-profil/{pengajuan}/reject', [PersetujuanProfilController::class, 'reject'])->name('persetujuan-profil.reject');

    // Akun staf (pasien tidak dibuat dari sini).
    Route::get('/akun-staf', [AkunStafController::class, 'index'])->name('akun-staf.index');
    Route::get('/akun-staf/create', [AkunStafController::class, 'create'])->name('akun-staf.create');
    Route::post('/akun-staf', [AkunStafController::class, 'store'])->name('akun-staf.store');
    Route::get('/akun-staf/{user}/edit', [AkunStafController::class, 'edit'])->name('akun-staf.edit');
    Route::put('/akun-staf/{user}', [AkunStafController::class, 'update'])->name('akun-staf.update');

    // Basis pengetahuan chatbot.
    Route::get('/pengetahuan-chatbot', [PengetahuanChatbotController::class, 'index'])->name('pengetahuan-chatbot.index');
    Route::get('/pengetahuan-chatbot/create', [PengetahuanChatbotController::class, 'create'])->name('pengetahuan-chatbot.create');
    Route::post('/pengetahuan-chatbot', [PengetahuanChatbotController::class, 'store'])->name('pengetahuan-chatbot.store');
    Route::get('/pengetahuan-chatbot/{pengetahuan}/edit', [PengetahuanChatbotController::class, 'edit'])->name('pengetahuan-chatbot.edit');
    Route::put('/pengetahuan-chatbot/{pengetahuan}', [PengetahuanChatbotController::class, 'update'])->name('pengetahuan-chatbot.update');
    Route::delete('/pengetahuan-chatbot/{pengetahuan}', [PengetahuanChatbotController::class, 'destroy'])->name('pengetahuan-chatbot.destroy');
});
