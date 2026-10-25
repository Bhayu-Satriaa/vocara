<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KartuQrController extends Controller
{
    /** Kartu QR pribadi pasien. Isi QR hanya token acak, bukan NIK atau data medis. */
    public function tampil(Request $request): View
    {
        $pasien = $request->user()->patients()->firstOrFail();

        ActivityLog::catat('lihat_kartu_qr', 'Pasien membuka kartu QR', $pasien);

        return view('pasien.kartu-qr', ['pasien' => $pasien]);
    }
}
