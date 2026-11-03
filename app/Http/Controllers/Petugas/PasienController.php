<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Petugas\SimpanPasienWalkInRequest;
use App\Models\ActivityLog;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PasienController extends Controller
{
    /**
     * Tampilkan form pendaftaran pasien walk-in baru.
     */
    public function create(): View
    {
        return view('petugas.pasien.create');
    }

    /**
     * Simpan data pasien walk-in dan buat token QR serta No RM secara otomatis.
     */
    public function store(SimpanPasienWalkInRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $verifikasiLangsung = $request->boolean('verifikasi_langsung', true);

        $pasien = DB::transaction(function () use ($validated, $verifikasiLangsung, $request) {
            $data = [
                'user_id' => null, // Pasien walk-in tidak memiliki akun login pengguna
                'nama_lengkap' => $validated['nama_lengkap'],
                'tgl_lahir' => $validated['tgl_lahir'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'no_wa' => $validated['no_wa'],
                'alamat' => $validated['alamat'] ?? null,
                'golongan_darah' => $validated['golongan_darah'] ?? '-',
                'alergi' => $validated['alergi'] ?? null,
                'hubungan_keluarga' => 'diri sendiri',
                'is_utama' => false,
            ];

            if ($verifikasiLangsung) {
                $data['verification_status'] = 'terverifikasi';
                $data['verified_by'] = $request->user()->id;
                $data['verified_at'] = now();
            } else {
                $data['verification_status'] = 'belum';
            }

            $pasienBaru = Patient::create($data);

            ActivityLog::catat(
                'daftar_pasien_walkin',
                "Petugas mendaftarkan pasien walk-in {$pasienBaru->nama_lengkap} (RM: {$pasienBaru->no_rm})" .
                ($verifikasiLangsung ? ' dan langsung memverifikasi identitas fisik KTP' : ''),
                $pasienBaru
            );

            return $pasienBaru;
        });

        return redirect()
            ->route('petugas.pasien.show', $pasien)
            ->with('sukses', "Pasien {$pasien->nama_lengkap} berhasil didaftarkan dengan No. RM {$pasien->no_rm}.");
    }

    /**
     * Tampilkan detail pasien walk-in, kartu QR, dan tombol aksi (cetak / kirim WA).
     */
    public function show(Patient $pasien): View
    {
        // Format nomor WhatsApp untuk wa.me (jika diawali 08, ubah jadi 628)
        $cleanWa = preg_replace('/[^0-9]/', '', (string) $pasien->no_wa);
        if (str_starts_with($cleanWa, '0')) {
            $cleanWa = '62' . substr($cleanWa, 1);
        }

        $pesanWa = rawurlencode(
            "Halo {$pasien->nama_lengkap},\n\n" .
            "Anda telah terdaftar sebagai pasien di Klinik Sehat.\n" .
            "Nomor Rekam Medis (RM): {$pasien->no_rm}\n" .
            "Token QR Anda: {$pasien->qr_token}\n\n" .
            "Harap simpan informasi ini saat berkunjung ke klinik."
        );

        $waLink = $cleanWa ? "https://wa.me/{$cleanWa}?text={$pesanWa}" : null;

        return view('petugas.pasien.show', [
            'pasien' => $pasien->load('verifier'),
            'waLink' => $waLink,
        ]);
    }

    /**
     * Tampilan cetak kartu pasien ukuran saku/ID Card.
     */
    public function kartu(Patient $pasien): View
    {
        return view('petugas.pasien.kartu', [
            'pasien' => $pasien,
        ]);
    }
}

