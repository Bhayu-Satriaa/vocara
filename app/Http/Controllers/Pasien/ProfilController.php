<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanProfilPasienRequest;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\PatientProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilController extends Controller
{
    /**
     * Halaman Profil: kartu semua data diri milik akun ini + penanda mana yang sedang
     * dipakai. Jika belum ada profil yang disetujui, tampilkan info pengajuan atau form "Lengkapi Profil".
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('pasien.profil', [
            'profil' => $user->patients()->terurut()->get(),
            'pengajuanMenunggu' => $user->profileRequests()->where('status', 'menunggu')->latest()->get(),
            'pengajuanRiwayat' => $user->profileRequests()->whereIn('status', ['disetujui', 'ditolak'])->latest()->limit(5)->get(),
        ]);
    }

    /** Form satu data diri baru (diri sendiri atau keluarga). */
    public function tambah(Request $request): View
    {
        return view('pasien.profil-form', [
            'pasien' => null,
            'judul' => 'Tambah data diri',
            'catatan' => 'Isi sesuai KTP orang yang mau didaftarkan. Pengajuan akan dikirimkan ke admin untuk persetujuan. Nomor rekam medis dan kartu QR akan dibuat otomatis setelah disetujui.',
            'aksi' => route('pasien.profil.tambah.simpan'),
            'metode' => 'POST',
            'tombol' => 'Kirim pengajuan ke admin',
            'pilihUtama' => $request->user()->patients()->exists(),
            'namaDefault' => '',
            'hubungan' => null,
        ]);
    }

    /** Form perubahan satu data diri milik akun ini. */
    public function ubah(Request $request, Patient $pasien): View
    {
        $this->pastikanMilikSesi($request, $pasien);

        return view('pasien.profil-form', [
            'pasien' => $pasien,
            'judul' => 'Ubah data diri',
            'catatan' => 'Nomor rekam medis dan kartu QR tidak berubah saat data diri diperbarui.',
            'aksi' => route('pasien.profil.perbarui', $pasien),
            'metode' => 'PUT',
            'tombol' => 'Simpan perubahan',
            'pilihUtama' => false,
            'namaDefault' => '',
            'hubungan' => $pasien->hubungan_keluarga,
        ]);
    }

    /**
     * Simpan data diri pertama (langkah "Lengkapi Profil" setelah login Google), atau
     * perbaiki data diri yang sedang dipakai.
     * Untuk pendaftaran pertama, data dikirim sebagai pengajuan ke admin terlebih dahulu.
     */
    public function update(SimpanProfilPasienRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        unset($data['is_utama']);  // data diri pertama otomatis jadi yang dipakai saat disetujui

        $pasien = $user->profilAktif();

        if ($pasien) {
            $pasien->update($data);
            ActivityLog::catat('ubah_data_diri', "Pasien mengubah data diri {$pasien->nama_lengkap}", $pasien);

            return redirect()
                ->route('pasien.profil')
                ->with('sukses', "Data diri {$pasien->nama_lengkap} diperbarui.");
        }

        // Pengajuan profil pertama dikirim ke admin
        $pengajuan = PatientProfileRequest::create($data + [
            'user_id' => $user->id,
            'is_utama' => true,
            'status' => 'menunggu',
        ]);

        ActivityLog::catat('ajukan_profil', 'Pasien mengajukan profil pertama', $pengajuan);

        return redirect()
            ->route('pasien.profil')
            ->with('sukses', 'Pengajuan profil berhasil dikirim ke admin. Mohon menunggu persetujuan admin untuk penerbitan Nomor Rekam Medis dan Kartu QR.');
    }

    /** Multi-profile: tambah data diri kedua dan seterusnya untuk akun yang sama (mengirim permohonan ke admin). */
    public function simpanBaru(SimpanProfilPasienRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $dipakaiSekarang = (bool) ($data['is_utama'] ?? false);
        unset($data['is_utama']);

        $pengajuan = PatientProfileRequest::create($data + [
            'user_id' => $user->id,
            'is_utama' => $dipakaiSekarang,
            'status' => 'menunggu',
        ]);

        ActivityLog::catat('ajukan_profil', "Pasien mengajukan data diri {$pengajuan->nama_lengkap} ({$pengajuan->hubungan_keluarga})", $pengajuan);

        return redirect()
            ->route('pasien.profil')
            ->with('sukses', "Pengajuan data diri {$pengajuan->nama_lengkap} berhasil dikirim ke admin dan sedang menunggu persetujuan.");
    }

    /** Perbaiki satu data diri tertentu milik akun ini. */
    public function perbarui(SimpanProfilPasienRequest $request, Patient $pasien): RedirectResponse
    {
        $data = $request->validated();
        unset($data['is_utama']);  // pemindahan profil aktif lewat tombol "Pakai data diri ini"

        $pasien->update($data);

        ActivityLog::catat('ubah_data_diri', "Pasien mengubah data diri {$pasien->nama_lengkap}", $pasien);

        return redirect()
            ->route('pasien.profil')
            ->with('sukses', "Data diri {$pasien->nama_lengkap} diperbarui.");
    }

    /** Pilih data diri mana yang dipakai untuk kartu QR dan booking. */
    public function aktifkan(Request $request, Patient $pasien): RedirectResponse
    {
        $this->pastikanMilikSesi($request, $pasien);

        $pasien->jadikanUtama();

        ActivityLog::catat('pilih_data_diri', "Pasien memakai data diri {$pasien->nama_lengkap}", $pasien);

        return redirect()->route('pasien.profil')->with(
            'sukses',
            "Kartu QR dan booking sekarang memakai data diri {$pasien->nama_lengkap} ({$pasien->no_rm})."
        );
    }

    /** Data diri akun lain tidak boleh dibuka, diubah, ataupun dipilih. */
    private function pastikanMilikSesi(Request $request, Patient $pasien): void
    {
        abort_unless($pasien->user_id === $request->user()->id, 403, 'Data diri ini bukan milik akun kamu.');
    }
}
