<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Tamu: staf masuk dengan email + password (akun dibuat admin), pasien lewat Google.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'form'])->name('login');
    Route::post('/login', [LoginController::class, 'masuk'])->name('login.masuk');

    Route::get('/auth/google', [GoogleController::class, 'arahkan'])->name('google.arahkan');
    Route::get('/auth/google/callback', [GoogleController::class, 'panggilBalik'])->name('google.balik');
});

Route::post('/logout', [LoginController::class, 'keluar'])
    ->middleware('auth')
    ->name('logout');
