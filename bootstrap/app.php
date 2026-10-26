<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Route per wilayah kerja: satu file per modul supaya beberapa orang bisa
        // bekerja tanpa berebut file route yang sama.
        then: function (): void {
            foreach (['auth', 'pasien', 'petugas', 'dokter', 'apoteker', 'admin'] as $modul) {
                Route::middleware('web')->group(base_path("routes/{$modul}.php"));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            // Dipakai di route: ->middleware('role:admin')
            'role' => \App\Http\Middleware\PastikanRole::class,
            // Pasien wajib lengkapi profil (nomor WA) sebelum booking / pakai QR.
            'profil' => \App\Http\Middleware\PastikanProfilLengkap::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
