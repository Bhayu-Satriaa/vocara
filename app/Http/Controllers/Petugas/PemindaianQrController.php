<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\QrScanLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    /** Data pasien hasil scan + tombol verifikasi KTP. */
    public function hasil(Patient $pasien): View
    {
        return view('petugas.hasil', [
            'pasien' => $pasien->load('verifier'),
            'riwayatScan' => $pasien->qrScanLogs()->with('petugas')->latest('id')->limit(5)->get(),
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
