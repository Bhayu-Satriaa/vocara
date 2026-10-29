<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KartuQrController extends Controller
{
    /** Kartu QR pribadi pasien. Isi QR hanya token acak, bukan data identitas atau data medis. */
    public function tampil(Request $request): View
    {
        // Multi-profile: kartu yang ditampilkan adalah data diri yang sedang dipakai.
        $pasien = $request->user()->profilAktif();
        abort_if($pasien === null, 404);

        ActivityLog::catat('lihat_kartu_qr', 'Pasien membuka kartu QR', $pasien);

        return view('pasien.kartu-qr', [
            'pasien' => $pasien,
            'jumlahProfil' => $request->user()->patients()->count(),
        ]);
    }
}
