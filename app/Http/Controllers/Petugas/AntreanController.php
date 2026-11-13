<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Poli;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AntreanController extends Controller
{
    /**
     * Tampilkan daftar antrean hari ini dan daftar booking online (Tugas 4).
     */
    public function index(Request $request): View
    {
        $tanggal = $request->input('tanggal', today()->toDateString());
        $poliId = $request->input('poli_id');
        $status = $request->input('status', 'aktif');
        $q = trim($request->input('q', ''));

        // Query antrean
        $antreanQuery = Visit::with(['patient', 'poli', 'doctor.user'])
            ->whereDate('tgl_kunjungan', $tanggal);

        if ($poliId) {
            $antreanQuery->where('poli_id', $poliId);
        }

        if ($status === 'aktif') {
            $antreanQuery->whereIn('status', ['menunggu', 'diperiksa', 'farmasi', 'pembayaran']);
        } elseif ($status !== 'semua' && in_array($status, Visit::STATUS)) {
            $antreanQuery->where('status', $status);
        }

        if ($q !== '') {
            $antreanQuery->where(function ($query) use ($q) {
                if (is_numeric($q)) {
                    $query->where('no_antrian', (int) $q);
                }
                $query->orWhereHas('patient', function ($pQuery) use ($q) {
                    $pQuery->where('nama_lengkap', 'like', "%{$q}%")
                        ->orWhere('no_rm', 'like', "%{$q}%");
                });
            });
        }

        // Urutkan status aktif terlebih dahulu, baru nomor antrean
        $antreans = $antreanQuery
            ->orderByRaw("FIELD(status, 'diperiksa', 'menunggu', 'farmasi', 'pembayaran', 'selesai', 'batal', 'booking')")
            ->orderBy('no_antrian', 'asc')
            ->paginate(20)
            ->withQueryString();

        // Statistik ringkas hari ini
        $baseStatQuery = Visit::whereDate('tgl_kunjungan', $tanggal);
        if ($poliId) {
            $baseStatQuery->where('poli_id', $poliId);
        }

        $stats = [
            'total' => (clone $baseStatQuery)->where('status', '!=', 'booking')->count(),
            'menunggu' => (clone $baseStatQuery)->where('status', 'menunggu')->count(),
            'diperiksa' => (clone $baseStatQuery)->where('status', 'diperiksa')->count(),
            'selesai' => (clone $baseStatQuery)->where('status', 'selesai')->count(),
            'batal' => (clone $baseStatQuery)->where('status', 'batal')->count(),
            'booking_pending' => Visit::where('status', 'booking')->whereDate('tgl_kunjungan', '>=', today())->count(),
        ];

        // Daftar booking online yang menunggu konfirmasi (status booking)
        $bookingQuery = Visit::with(['patient', 'poli', 'doctor.user'])
            ->where('status', 'booking')
            ->whereDate('tgl_kunjungan', '>=', today())
            ->orderBy('tgl_kunjungan', 'asc')
            ->orderBy('jam', 'asc');

        if ($poliId) {
            $bookingQuery->where('poli_id', $poliId);
        }

        $bookingList = $bookingQuery->limit(20)->get();

        $polis = Poli::where('is_active', true)->orderBy('nama')->get();

        return view('petugas.antrean.index', [
            'antreans' => $antreans,
            'stats' => $stats,
            'bookingList' => $bookingList,
            'polis' => $polis,
            'filterTanggal' => $tanggal,
            'filterPoliId' => $poliId,
            'filterStatus' => $status,
            'searchQuery' => $q,
        ]);
    }

    /**
     * Panggil nomor antrean pasien (suara panggilan & audit log).
     */
    public function panggil(Request $request, Visit $visit): JsonResponse|RedirectResponse
    {
        $pasien = $visit->patient;
        $poli = $visit->poli;

        $pesanSuara = sprintf(
            'Nomor antrean %s, atas nama %s, silakan menuju %s.',
            $visit->no_antrian ?? '-',
            $pasien->nama_lengkap,
            $poli ? $poli->nama : 'ruang pemeriksaan'
        );

        ActivityLog::catat(
            'panggil_antrean',
            "Petugas memanggil antrean #{$visit->no_antrian} ({$pasien->nama_lengkap}) menuju {$poli?->nama}",
            $visit
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Antrean #{$visit->no_antrian} dipanggil.",
                'teks_suara' => $pesanSuara,
            ]);
        }

        return back()
            ->with('sukses', "Memanggil antrean #{$visit->no_antrian} ({$pasien->nama_lengkap}).")
            ->with('panggilan_suara', $pesanSuara);
    }

    /**
     * Ubah status antrean dengan validasi transisi yang sah.
     */
    public function updateStatus(Request $request, Visit $visit): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', Visit::STATUS)],
        ]);

        $statusBaru = $validated['status'];
        $statusLama = $visit->status;

        // Cegah perubahan status sembarangan jika kunjungan sudah selesai atau dibatalkan
        if (in_array($statusLama, ['selesai', 'batal'])) {
            return back()->with('peringatan', "Kunjungan yang sudah {$statusLama} tidak dapat diubah statusnya lagi.");
        }

        $visit->update(['status' => $statusBaru]);

        ActivityLog::catat(
            'ubah_status_antrean',
            "Petugas mengubah status kunjungan #{$visit->no_antrian} ({$visit->patient->nama_lengkap}) dari {$statusLama} ke {$statusBaru}",
            $visit
        );

        return back()->with('sukses', "Status antrean #{$visit->no_antrian} berhasil diperbarui menjadi '{$statusBaru}'.");
    }

    /**
     * Batalkan antrean pasien dengan alasan wajib.
     */
    public function batal(Request $request, Visit $visit): RedirectResponse
    {
        $validated = $request->validate([
            'alasan' => ['required', 'string', 'max:255'],
        ], [
            'alasan.required' => 'Alasan pembatalan antrean wajib diisi.',
        ]);

        if ($visit->status === 'batal') {
            return back()->with('peringatan', 'Antrean ini sudah dibatalkan sebelumnya.');
        }

        $statusLama = $visit->status;
        $visit->update(['status' => 'batal']);

        ActivityLog::catat(
            'batal_antrean',
            "Petugas membatalkan kunjungan #{$visit->no_antrian} ({$visit->patient->nama_lengkap}) status awal: {$statusLama}. Alasan: {$validated['alasan']}",
            $visit
        );

        return back()->with('sukses', "Antrean #{$visit->no_antrian} ({$visit->patient->nama_lengkap}) berhasil dibatalkan.");
    }

    /**
     * Konfirmasi booking online menjadi antrean hari ini.
     */
    public function konfirmasiBooking(Request $request, Visit $visit): RedirectResponse
    {
        if ($visit->status !== 'booking') {
            return back()->with('peringatan', 'Hanya janji temu berstatus booking yang dapat dikonfirmasi.');
        }

        DB::transaction(function () use ($request, $visit) {
            // Hitung nomor antrean di poli & dokter bersangkutan untuk tanggal kunjungan
            $noAntrean = ((int) Visit::where('poli_id', $visit->poli_id)
                ->where('doctor_id', $visit->doctor_id)
                ->whereDate('tgl_kunjungan', $visit->tgl_kunjungan)
                ->max('no_antrian')) + 1;

            $visit->update([
                'status' => 'menunggu',
                'no_antrian' => $noAntrean,
                'checkin_metode' => 'manual',
                'checkin_at' => now(),
                'checkin_by' => $request->user()->id,
            ]);

            ActivityLog::catat(
                'konfirmasi_booking',
                "Petugas mengonfirmasi booking online {$visit->patient->nama_lengkap} menjadi antrean #{$noAntrean}",
                $visit
            );
        });

        return back()->with('sukses', "Booking online {$visit->patient->nama_lengkap} berhasil dikonfirmasi ke antrean #{$visit->no_antrian}.");
    }
}

