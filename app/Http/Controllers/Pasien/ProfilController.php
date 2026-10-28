<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanProfilPasienRequest;
use App\Models\ActivityLog;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilController extends Controller
{
    /**
     * Halaman Profil: kartu semua data diri milik akun ini + penanda mana yang sedang
     * dipakai. Belum ada data diri sama sekali? Halaman ini masih jadi "Lengkapi Profil".
     */
    public function edit(Request $request): View
    {
        return view('pasien.profil', [
            'profil' => $request->user()->patients()->terurut()->get(),
        ]);
    }

    /** Form satu data diri baru (diri sendiri atau keluarga). */
    public function tambah(Request $request): View
    {
        return view('pasien.profil-form', [
            'pasien' => null,
            'judul' => 'Tambah data diri',
            'catatan' => 'Isi sesuai KTP orang yang mau didaftarkan. Nomor rekam medis dan kartu QR dibuatkan otomatis.',
            'aksi' => route('pasien.profil.tambah.simpan'),
            'metode' => 'POST',
            'tombol' => 'Simpan data diri',
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
     */
    public function update(SimpanProfilPasienRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        unset($data['is_utama']);  // data diri pertama otomatis jadi yang dipakai

        $pasien = $user->profilAktif();

        if ($pasien) {
            $pasien->update($data);
        } else {
            // Nomor rekam medis dan QR diisi otomatis oleh model Patient.
            $pasien = Patient::query()->create($data + ['user_id' => $user->id]);
        }

        ActivityLog::catat('lengkapi_profil', 'Pasien melengkapi profil', $pasien);

        return redirect()
            ->route('dashboard')
            ->with('sukses', "Profil tersimpan. Nomor rekam medis kamu {$pasien->no_rm}.");
    }

    /** Multi-profile: tambah data diri kedua dan seterusnya untuk akun yang sama. */
    public function simpanBaru(SimpanProfilPasienRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $dipakaiSekarang = (bool) ($data['is_utama'] ?? false);
        unset($data['is_utama']);

        $pasien = Patient::query()->create($data + ['user_id' => $user->id]);

        if ($dipakaiSekarang) {
            $pasien->jadikanUtama();
        }

        ActivityLog::catat('tambah_data_diri', "Pasien menambah data diri {$pasien->nama_lengkap}", $pasien);

        return redirect()
            ->route('pasien.profil')
            ->with('sukses', "Data diri {$pasien->nama_lengkap} tersimpan. Nomor rekam medis {$pasien->no_rm}.");
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
