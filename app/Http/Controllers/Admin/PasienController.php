<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasienController extends Controller
{
    /** Data master pasien: daftar + cari berdasar nama, No RM, atau nomor WA. */
    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $status = $request->query('status', 'semua');
        $status = in_array($status, ['semua', 'belum', 'terverifikasi'], true) ? $status : 'semua';

        $pasien = Patient::query()
            ->with('verifier')
            ->when($cari !== '', function ($query) use ($cari) {
                $query->where(function ($q) use ($cari) {
                    $q->where('nama_lengkap', 'like', "%{$cari}%")
                        ->orWhere('no_rm', 'like', "%{$cari}%")
                        ->orWhere('no_wa', 'like', "%{$cari}%");
                });
            })
            ->when($status !== 'semua', fn ($query) => $query->where('verification_status', $status))
            ->orderBy('nama_lengkap')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pasien', [
            'pasien' => $pasien,
            'cari' => $cari,
            'status' => $status,
        ]);
    }

    /** Detail data diri satu pasien. */
    public function show(Patient $pasien): View
    {
        return view('admin.pasien-detail', [
            'pasien' => $pasien->load(['user', 'verifier']),
        ]);
    }
}
