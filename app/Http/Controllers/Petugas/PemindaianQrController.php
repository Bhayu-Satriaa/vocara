<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Poli;
use App\Models\QrScanLog;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pemindaian QR pasien oleh petugas pendaftaran.
 *
 * QR pasien isinya token acak (UUID) dari `patients.qr_token`. Hasil scan hanya
 * bisa dibuka petugas/admin yang sudah login, dan setiap scan dicatat di
 * `qr_scan_logs` + `activity_logs` (aturan di AGENTS.md).
 */
class PemindaianQrController extends Controller
{
    /** Form input token QR. */
    public function form(): View
    {
        return view('petugas.scan');
    }

    /** Cari pasien dari token hasil scan, lalu catat scan-nya. */
    public function cari(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['token' => ['required', 'string', 'max:64']],
            ['token.required' => 'Token QR wajib diisi — tempel isi QR pasien di sini.'],
        );

        $pasien = Patient::query()->where('qr_token', trim($data['token']))->first();

        if (! $pasien) {
            return back()
                ->withInput()
                ->with('peringatan', 'Token QR tidak dikenali. Pastikan yang discan adalah kartu QR pasien klinik ini.');
        }

        $this->catatScan($request, $pasien);

        return redirect()->route('petugas.scan.hasil', $pasien);
    }

    /** Data pasien hasil scan, status verifikasi KTP, & form check-in antrean. */
    public function hasil(Patient $pasien): View
    {
        // Cek kunjungan aktif hari ini
        $kunjunganHariIni = Visit::with(['doctor.user', 'poli'])
            ->where('patient_id', $pasien->id)
            ->whereDate('tgl_kunjungan', today())
            ->where('status', '!=', 'batal')
            ->latest('id')
            ->first();

        // Daftar poli & dokter aktif untuk check-in walk-in jika belum ada booking
        $polis = Poli::with(['doctors.user'])
            ->where('is_active', true)
            ->get();

        return view('petugas.hasil', [
            'pasien' => $pasien->load('verifier'),
            'riwayatScan' => $pasien->qrScanLogs()->with('petugas')->latest('id')->limit(5)->get(),
            'kunjunganHariIni' => $kunjunganHariIni,
            'polis' => $polis,
        ]);
    }

    /** Verifikasi identitas: petugas mencocokkan KTP, status jadi terverifikasi. */
    public function verifikasi(Request $request, Patient $pasien): RedirectResponse
    {
        if ($pasien->sudahTerverifikasi()) {
            return redirect()
                ->route('petugas.scan.hasil', $pasien)
                ->with('peringatan', 'Pasien ini sudah terverifikasi sebelumnya.');
        }

        $pasien->update([
            'verification_status' => 'terverifikasi',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $this->catatScan($request, $pasien, 'verifikasi');

        ActivityLog::catat(
            'verifikasi_ktp',
            "Petugas mencocokkan KTP {$pasien->no_rm} dan menandainya terverifikasi",
            $pasien,
        );

        return redirect()
            ->route('petugas.scan.hasil', $pasien)
            ->with('sukses', "Identitas {$pasien->nama_lengkap} ({$pasien->no_rm}) berhasil diverifikasi.");
    }

    /**
     * Check-in pasien ke antrean hari ini (Tugas 2).
     */
    public function checkin(Request $request, Patient $pasien): RedirectResponse
    {
        // 1. Cegah check-in ganda di hari yang sama
        $sudahCheckin = Visit::with('poli')
            ->where('patient_id', $pasien->id)
            ->whereDate('tgl_kunjungan', today())
            ->whereIn('status', ['menunggu', 'diperiksa', 'farmasi', 'pembayaran', 'selesai'])
            ->first();

        if ($sudahCheckin) {
            return redirect()
                ->route('petugas.scan.hasil', $pasien)
                ->with('peringatan', "Pasien sudah check-in hari ini dengan nomor antrean #{$sudahCheckin->no_antrian} di {$sudahCheckin->poli?->nama}.");
        }

        // 2. Cek apakah pasien punya booking janji temu hari ini yang statusnya masih 'booking'
        $booking = Visit::with(['poli', 'doctor.user'])
            ->where('patient_id', $pasien->id)
            ->whereDate('tgl_kunjungan', today())
            ->where('status', 'booking')
            ->first();

        $kunjungan = DB::transaction(function () use ($request, $pasien, $booking) {
            if ($booking) {
                // Generate nomor antrean untuk poli & dokter hari ini
                $noAntrean = ((int) Visit::where('poli_id', $booking->poli_id)
                    ->where('doctor_id', $booking->doctor_id)
                    ->whereDate('tgl_kunjungan', today())
                    ->max('no_antrian')) + 1;

                $booking->update([
                    'status' => 'menunggu',
                    'no_antrian' => $noAntrean,
                    'checkin_metode' => 'qr',
                    'checkin_at' => now(),
                    'checkin_by' => $request->user()->id,
                ]);

                return $booking;
            }

            // Jika walk-in (tanpa booking sebelumnya), validasi poli dan dokter
            $validated = $request->validate([
                'poli_id' => ['required', 'exists:polis,id'],
                'doctor_id' => ['required', 'exists:doctors,id'],
                'keluhan_awal' => ['nullable', 'string', 'max:255'],
            ], [
                'poli_id.required' => 'Poli tujuan wajib dipilih untuk check-in walk-in.',
                'doctor_id.required' => 'Dokter pemeriksa wajib dipilih.',
            ]);

            // Hitung nomor antrean
            $noAntrean = ((int) Visit::where('poli_id', $validated['poli_id'])
                ->where('doctor_id', $validated['doctor_id'])
                ->whereDate('tgl_kunjungan', today())
                ->max('no_antrian')) + 1;

            // Pastikan jam slot unik untuk dokter tersebut hari ini
            $jamBase = now()->startOfMinute();
            while (Visit::where('doctor_id', $validated['doctor_id'])
                ->whereDate('tgl_kunjungan', today())
                ->where('jam', $jamBase->format('H:i:s'))
                ->where('status', '!=', 'batal')
                ->exists()) {
                $jamBase->addMinute();
            }

            return Visit::create([
                'patient_id' => $pasien->id,
                'doctor_id' => $validated['doctor_id'],
                'poli_id' => $validated['poli_id'],
                'tgl_kunjungan' => today(),
                'jam' => $jamBase->format('H:i:s'),
                'no_antrian' => $noAntrean,
                'status' => 'menunggu',
                'sumber' => 'walk_in',
                'keluhan_awal' => $validated['keluhan_awal'] ?? null,
                'checkin_metode' => 'qr',
                'checkin_at' => now(),
                'checkin_by' => $request->user()->id,
            ]);
        });

        $this->catatScan($request, $pasien, 'checkin');

        ActivityLog::catat(
            'checkin_pasien',
            "Pasien {$pasien->nama_lengkap} ({$pasien->no_rm}) check-in ke antrean #{$kunjungan->no_antrian} di {$kunjungan->poli?->nama}",
            $kunjungan
        );

        return redirect()
            ->route('petugas.scan.hasil', $pasien)
            ->with('sukses', "Pasien berhasil check-in! Nomor Antrean: #{$kunjungan->no_antrian} di {$kunjungan->poli?->nama}.");
    }

    /** Satu baris riwayat scan per pemindaian. */
    private function catatScan(Request $request, Patient $pasien, ?string $konteks = null): void
    {
        QrScanLog::query()->create([
            'patient_id' => $pasien->id,
            'scanned_by' => $request->user()->id,
            'konteks' => $konteks ?? ($pasien->sudahTerverifikasi() ? 'checkin' : 'verifikasi'),
        ]);

        ActivityLog::catat('scan_qr', "Petugas memindai kartu QR {$pasien->no_rm}", $pasien);
    }
}
