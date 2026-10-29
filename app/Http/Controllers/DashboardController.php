<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Poli;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Halaman utama setelah login; isinya menyesuaikan role. */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return view("dashboard.{$user->role}", [
            'user' => $user,
            'statistik' => $user->punyaRole('admin') ? $this->statistikAdmin() : [],
            // Multi-profile: dashboard menampilkan data diri yang sedang dipakai akun ini.
            'pasien' => $user->punyaRole('pasien') ? $user->profilAktif() : null,
        ]);
    }

    /** Angka diambil langsung dari database, bukan dikira-kira. */
    private function statistikAdmin(): array
    {
        return [
            'Akun staf aktif' => User::query()->where('role', '!=', 'pasien')->where('is_active', true)->count(),
            'Dokter' => Doctor::query()->count(),
            'Poli' => Poli::query()->count(),
            'Pasien terdaftar' => Patient::query()->count(),
            'Obat' => Medicine::query()->count(),
            'Obat stok menipis' => Medicine::query()->stokMenipis()->count(),
            'Kunjungan hari ini' => Visit::query()->whereDate('tgl_kunjungan', today())->count(),
        ];
    }
}
