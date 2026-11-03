<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Poli;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
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
            'analitik' => $user->punyaRole('admin') ? $this->analitikAdmin(request()->query('bulan', '')) : null,
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
            'Kunjungan hari ini' => Visit::query()->whereDate('tgl_kunjungan', today())->count(),
        ];
    }

    /** Data grafik + rekap dashboard admin: kunjungan bulan yang dipilih + poli tersibuk. */
    private function analitikAdmin(string $bulan = ''): array
    {
        $periode = preg_match('/^\d{4}-\d{2}$/', $bulan)
            ? Carbon::createFromFormat('Y-m', $bulan)->startOfMonth()
            : today()->startOfMonth();

        $mulai = $periode->copy()->startOfMonth();
        $selesai = $periode->copy()->endOfMonth();
        $jumlahHari = (int) $selesai->format('j');

        $perHari = Visit::query()
            ->selectRaw('tgl_kunjungan, count(*) as total')
            ->whereDate('tgl_kunjungan', '>=', $mulai->toDateString())
            ->whereDate('tgl_kunjungan', '<=', $selesai->toDateString())
            ->groupBy('tgl_kunjungan')
            ->pluck('total', 'tgl_kunjungan');

        $label = [];
        $nilai = [];
        for ($i = 1; $i <= $jumlahHari; $i++) {
            $tanggal = $mulai->copy()->day($i)->toDateString();
            $label[] = (string) $i;
            $nilai[] = (int) ($perHari[$tanggal] ?? 0);
        }

        $perPoli = Visit::query()
            ->selectRaw('poli_id, count(*) as total')
            ->whereDate('tgl_kunjungan', '>=', $mulai->toDateString())
            ->whereDate('tgl_kunjungan', '<=', $selesai->toDateString())
            ->groupBy('poli_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('poli')
            ->get();

        $total = array_sum($nilai);
        $maksHarian = max($nilai ?: [0]);
        $hariTersibuk = $maksHarian > 0 ? array_search($maksHarian, $nilai, true) + 1 : null;

        $opsiBulan = [];
        for ($i = 0; $i < 12; $i++) {
            $b = today()->startOfMonth()->subMonths($i);
            $opsiBulan[] = [
                'nilai' => $b->format('Y-m'),
                'label' => $b->locale('id')->translatedFormat('F Y'),
            ];
        }

        return [
            'bulanNilai' => $mulai->format('Y-m'),
            'bulanLabel' => $mulai->locale('id')->translatedFormat('F Y'),
            'opsiBulan' => $opsiBulan,
            'labelHari' => $label,
            'nilaiHari' => $nilai,
            'labelPoli' => $perPoli->map(fn ($v) => $v->poli?->nama ?? 'Tanpa poli')->all(),
            'nilaiPoli' => $perPoli->map(fn ($v) => (int) $v->total)->all(),
            'rekap' => [
                'total' => $total,
                'rata' => $jumlahHari > 0 ? round($total / $jumlahHari, 1) : 0,
                'tersibuk' => $hariTersibuk ? $mulai->copy()->day($hariTersibuk)->locale('id')->translatedFormat('d F Y') : null,
                'kunjunganTersibuk' => $maksHarian,
                'poliTersibuk' => $perPoli->first()?->poli?->nama,
                'kunjunganPoliTersibuk' => (int) ($perPoli->first()?->total ?? 0),
            ],
        ];
    }
}
