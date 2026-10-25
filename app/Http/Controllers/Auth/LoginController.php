<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Halaman masuk untuk **staf** (email + password). Pasien tidak lewat sini —
     * pasien memakai Google OAuth (lihat docs/DECISIONS.md).
     */
    public function form(): View
    {
        return view('auth.login');
    }

    public function masuk(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($kredensial, $request->boolean('ingat'))) {
            throw ValidationException::withMessages(['email' => 'Email atau password salah.']);
        }

        $user = Auth::user();

        if (! $user->staf() || ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            throw ValidationException::withMessages([
                'email' => $user->staf()
                    ? 'Akun ini sedang tidak aktif. Hubungi admin.'
                    : 'Akun pasien masuk lewat halaman pasien, bukan halaman staf.',
            ]);
        }

        $request->session()->regenerate();
        ActivityLog::catat('login', 'Login staf sebagai '.$user->role);

        return redirect()->intended(route('dashboard'));
    }

    public function keluar(Request $request): RedirectResponse
    {
        ActivityLog::catat('logout', 'Logout staf');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('sukses', 'Kamu sudah keluar.');
    }
}
