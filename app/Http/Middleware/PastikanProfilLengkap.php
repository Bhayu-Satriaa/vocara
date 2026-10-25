<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanProfilLengkap
{
    /**
     * Fitur pasien (booking, kartu QR) baru boleh dipakai setelah profil lengkap —
     * nomor WA wajib terisi (aturan di AGENTS.md / docs/FEATURES.md).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->punyaRole('pasien') && ! $user->profilLengkap()) {
            return redirect()->route('pasien.profil')->with(
                'peringatan',
                'Lengkapi profil dulu (nomor WA wajib) sebelum memakai fitur ini.',
            );
        }

        return $next($request);
    }
}
