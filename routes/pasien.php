<?php

use App\Http\Controllers\Pasien\ChatController;
use App\Http\Controllers\Pasien\KartuQrController;
use App\Http\Controllers\Pasien\ProfilController;
use Illuminate\Support\Facades\Route;

// Wilayah pasien. Akun pasien hanya boleh masuk lewat sini.
Route::middleware(['auth', 'role:pasien'])->group(function () {
    // Halaman Profil: kartu semua data diri + pilih mana yang dipakai (multi-profile).
    Route::get('/profil', [ProfilController::class, 'edit'])->name('pasien.profil');

    // Lengkapi Profil (langkah setelah login Google): nomor WA wajib,
    // QR dibuat otomatis setelah data pertama tersimpan.
    Route::put('/profil', [ProfilController::class, 'update'])->name('pasien.profil.simpan');

    // Tambah data diri lain untuk akun yang sama (keluarga) — dapat no_rm & QR sendiri.
    Route::get('/profil/tambah', [ProfilController::class, 'tambah'])->name('pasien.profil.tambah');
    Route::post('/profil/tambah', [ProfilController::class, 'simpanBaru'])->name('pasien.profil.tambah.simpan');

    // Ubah satu data diri, dan pindahkan data diri yang sedang dipakai.
    Route::get('/profil/{pasien}/ubah', [ProfilController::class, 'ubah'])->name('pasien.profil.ubah');
    Route::put('/profil/{pasien}', [ProfilController::class, 'perbarui'])->name('pasien.profil.perbarui');
    Route::post('/profil/{pasien}/pakai', [ProfilController::class, 'aktifkan'])->name('pasien.profil.pakai');

    // Butuh profil lengkap (middleware `profil`).
    Route::get('/kartu-qr', [KartuQrController::class, 'tampil'])
        ->middleware('profil')
        ->name('pasien.qr');

    // Chatbot dasar (Tahap 1): tanya jawab dari basis pengetahuan, tanpa AI.
    Route::get('/chat', [ChatController::class, 'index'])
        ->middleware('profil')
        ->name('pasien.chat');
    Route::post('/chat/kirim', [ChatController::class, 'kirim'])
        ->middleware('profil')
        ->name('pasien.chat.kirim');

    // Booking janji temu dan riwayat kunjungan pasien ditambahkan di sini.
});
