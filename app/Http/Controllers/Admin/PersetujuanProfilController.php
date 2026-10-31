<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\PatientProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PersetujuanProfilController extends Controller
{
    /**
     * Menampilkan daftar permohonan profil pasien yang masuk ke admin.
     * Tempat penampungan request pendaftaran profil sebelum disetujui.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'menunggu');

        $query = PatientProfileRequest::with(['user', 'approver', 'patient'])->latest();

        if (in_array($status, ['menunggu', 'disetujui', 'ditolak'], true)) {
            $query->where('status', $status);
        }

        $permohonan = $query->paginate(15)->withQueryString();

        $jumlahMenunggu = PatientProfileRequest::where('status', 'menunggu')->count();

        return view('admin.persetujuan-profil', [
            'permohonan' => $permohonan,
            'statusFilter' => $status,
            'jumlahMenunggu' => $jumlahMenunggu,
        ]);
    }

    /**
     * Admin menyetujui (approve) permohonan profil pasien.
     * Saat disetujui:
     * 1. Data resmi masuk ke tabel `patients`.
     * 2. Nomor Rekam Medis (no_rm) dan token QR dibuat otomatis oleh model Patient.
     * 3. Status pengajuan berubah menjadi 'disetujui'.
     */
    public function approve(Request $request, PatientProfileRequest $pengajuan): RedirectResponse
    {
        if ($pengajuan->status !== 'menunggu') {
            return back()->with('peringatan', 'Permohonan ini sudah diproses sebelumnya.');
        }

        $patient = DB::transaction(function () use ($pengajuan, $request) {
            // Buat record pasien baru (no_rm dan qr_token otomatis digenerate oleh booted() di model Patient)
            $baru = Patient::create([
                'user_id' => $pengajuan->user_id,
                'nama_lengkap' => $pengajuan->nama_lengkap,
                'hubungan_keluarga' => $pengajuan->hubungan_keluarga,
                'no_wa' => $pengajuan->no_wa,
                'tgl_lahir' => $pengajuan->tgl_lahir,
                'jenis_kelamin' => $pengajuan->jenis_kelamin,
                'alamat' => $pengajuan->alamat,
                'golongan_darah' => $pengajuan->golongan_darah ?: '-',
                'alergi' => $pengajuan->alergi,
            ]);

            // Jika pemohon menginginkan ini jadi profil utama atau belum ada profil utama
            if ($pengajuan->is_utama) {
                $baru->jadikanUtama();
            }

            // Perbarui status pengajuan
            $pengajuan->update([
                'status' => 'disetujui',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'patient_id' => $baru->id,
            ]);

            return $baru;
        });

        ActivityLog::catat(
            'setujui_profil',
            "Admin menyetujui pendaftaran profil {$patient->nama_lengkap} (RM: {$patient->no_rm})",
            $patient
        );

        return back()->with(
            'sukses',
            "Pengajuan profil {$patient->nama_lengkap} berhasil disetujui! Nomor Rekam Medis: {$patient->no_rm} dan kartu QR telah aktif."
        );
    }

    /**
     * Admin menolak permohonan profil pasien.
     */
    public function reject(Request $request, PatientProfileRequest $pengajuan): RedirectResponse
    {
        if ($pengajuan->status !== 'menunggu') {
            return back()->with('peringatan', 'Permohonan ini sudah diproses sebelumnya.');
        }

        $request->validate([
            'catatan_admin' => ['nullable', 'string', 'max:255'],
        ]);

        $pengajuan->update([
            'status' => 'ditolak',
            'catatan_admin' => $request->input('catatan_admin', 'Data permohonan tidak sesuai.'),
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        ActivityLog::catat(
            'tolak_profil',
            "Admin menolak pengajuan profil {$pengajuan->nama_lengkap}",
            $pengajuan
        );

        return back()->with('sukses', "Pengajuan profil {$pengajuan->nama_lengkap} telah ditolak.");
    }
}

