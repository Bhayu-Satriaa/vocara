<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as RedirectKeGoogle;

class GoogleController extends Controller
{
    /**
     * Login Google hanya untuk role pasien. Akun staf tidak boleh lewat jalur ini
     * (aturan di AGENTS.md / docs/DECISIONS.md).
     */
    public function arahkan(): RedirectKeGoogle|RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Login Google belum diatur: GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET belum diisi di .env.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function panggilBalik(): RedirectResponse
    {
        $akunGoogle = Socialite::driver('google')->user();

        $user = User::query()->where('google_id', $akunGoogle->getId())->first()
            ?? User::query()->where('email', $akunGoogle->getEmail())->first();

        // Email yang sudah dipakai akun staf tidak boleh berubah jadi pasien.
        if ($user?->staf()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Email ini terdaftar sebagai akun staf. Staf masuk lewat halaman login, bukan Google.',
            ]);
        }

        if (! $user) {
            $user = User::query()->create([
                'name' => $akunGoogle->getName() ?: 'Pasien',
                'email' => $akunGoogle->getEmail(),
                'google_id' => $akunGoogle->getId(),
                'avatar' => $akunGoogle->getAvatar(),
                'password' => null,
                'role' => 'pasien',
                'is_active' => true,
            ]);

            ActivityLog::catat('daftar_pasien', 'Akun pasien baru lewat Google', $user);
        } else {
            $user->update([
                'google_id' => $akunGoogle->getId(),
                'avatar' => $akunGoogle->getAvatar(),
            ]);
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun ini sedang tidak aktif. Hubungi petugas.',
            ]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        ActivityLog::catat('login_google', 'Pasien masuk lewat Google');

        // Nomor WA wajib: profil belum lengkap → halaman Lengkapi Profil dulu.
        if (! $user->profilLengkap()) {
            return redirect()->route('pasien.profil')->with(
                'peringatan',
                'Isi dulu data diri dan nomor WA ya, supaya kartu QR dan booking bisa dipakai.',
            );
        }

        return redirect()->intended(route('dashboard'));
    }
}
