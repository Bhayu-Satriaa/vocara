<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanProfilPasienRequest;
use App\Models\ActivityLog;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilController extends Controller
{
    /** Halaman Lengkapi Profil: nomor WA wajib sebelum boleh booking / pakai QR. */
    public function edit(Request $request): View
    {
        return view('pasien.profil', [
            'pasien' => $request->user()->patients()->first(),
        ]);
    }

    public function update(SimpanProfilPasienRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $pasien = $user->patients()->first();

        if ($pasien) {
            $pasien->update($data);
        } else {
            // QR (qr_token) diisi otomatis oleh model Patient.
            $pasien = Patient::query()->create($data + [
                'user_id' => $user->id,
                'no_rm' => Patient::nomorRekamMedisBaru(),
            ]);
        }

        ActivityLog::catat('lengkapi_profil', 'Pasien melengkapi profil', $pasien);

        return redirect()
            ->route('dashboard')
            ->with('sukses', "Profil tersimpan. Nomor rekam medis kamu {$pasien->no_rm}.");
    }
}
