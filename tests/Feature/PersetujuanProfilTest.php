<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientProfileRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersetujuanProfilTest extends TestCase
{
    use RefreshDatabase;

    private function akunPasien(string $email = 'pasien@klinik.test'): User
    {
        /** @var \App\Models\User $user */
        return User::factory()->pasien()->create(['email' => $email]);
    }

    private function akunAdmin(): User
    {
        /** @var \App\Models\User $user */
        return User::factory()->role('admin')->create();
    }

    private function dataProfil(array $ubah = []): array
    {
        return array_merge([
            'nama_lengkap' => 'Budi Santoso',
            'hubungan_keluarga' => 'diri sendiri',
            'no_wa' => '081234567890',
            'tgl_lahir' => '1995-05-20',
            'jenis_kelamin' => 'L',
            'golongan_darah' => 'O',
            'alamat' => 'Jalan Kenangan No. 12',
        ], $ubah);
    }

    public function test_pasien_submit_profil_pertama_mengirim_request_menunggu_ke_admin(): void
    {
        $pasien = $this->akunPasien();

        $this->actingAs($pasien)
            ->put(route('pasien.profil.simpan'), $this->dataProfil())
            ->assertRedirect(route('pasien.profil'))
            ->assertSessionHas('sukses');

        // Belum masuk ke tabel patients
        $this->assertDatabaseCount('patients', 0);

        // Masuk ke tabel patient_profile_requests dengan status menunggu
        $this->assertDatabaseHas('patient_profile_requests', [
            'user_id' => $pasien->id,
            'nama_lengkap' => 'Budi Santoso',
            'status' => 'menunggu',
            'is_utama' => true,
        ]);

        // Halaman profil pasien menampilkan status menunggu persetujuan
        $this->actingAs($pasien)
            ->get(route('pasien.profil'))
            ->assertOk()
            ->assertSee('Pengajuan Profil Sedang Diproses Admin')
            ->assertSee('Budi Santoso');
    }

    public function test_pasien_menambah_profil_keluarga_mengirim_request_ke_admin(): void
    {
        $user = $this->akunPasien();

        // Misal sudah ada 1 profil diri sendiri yang aktif
        Patient::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Budi Utama',
            'hubungan_keluarga' => 'diri sendiri',
            'no_wa' => '081234567890',
            'tgl_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'is_utama' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pasien.profil.tambah.simpan'), [
                'nama_lengkap' => 'Sari Santoso',
                'hubungan_keluarga' => 'pasangan',
                'no_wa' => '081299998888',
                'tgl_lahir' => '1992-02-02',
                'jenis_kelamin' => 'P',
            ])
            ->assertRedirect(route('pasien.profil'))
            ->assertSessionHas('sukses');

        // Tabel patients masih hanya berisi 1 pasien lama
        $this->assertDatabaseCount('patients', 1);

        // Request masuk ke admin
        $this->assertDatabaseHas('patient_profile_requests', [
            'user_id' => $user->id,
            'nama_lengkap' => 'Sari Santoso',
            'hubungan_keluarga' => 'pasangan',
            'status' => 'menunggu',
        ]);
    }

    public function test_admin_bisa_melihat_daftar_pengajuan_profil(): void
    {
        $pasien = $this->akunPasien();
        $admin = $this->akunAdmin();

        PatientProfileRequest::create([
            'user_id' => $pasien->id,
            'nama_lengkap' => 'Calon Pasien',
            'hubungan_keluarga' => 'anak',
            'no_wa' => '081234567890',
            'tgl_lahir' => '2015-05-05',
            'jenis_kelamin' => 'L',
            'status' => 'menunggu',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.persetujuan-profil.index'))
            ->assertOk()
            ->assertSee('Persetujuan Profil Pasien')
            ->assertSee('Calon Pasien')
            ->assertSee('anak');
    }

    public function test_admin_approve_pengajuan_profil_menerbitkan_no_rm_dan_qr(): void
    {
        $pasien = $this->akunPasien();
        $admin = $this->akunAdmin();

        $pengajuan = PatientProfileRequest::create([
            'user_id' => $pasien->id,
            'nama_lengkap' => 'Budi Santoso',
            'hubungan_keluarga' => 'diri sendiri',
            'no_wa' => '081234567890',
            'tgl_lahir' => '1995-05-20',
            'jenis_kelamin' => 'L',
            'status' => 'menunggu',
            'is_utama' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.persetujuan-profil.approve', $pengajuan))
            ->assertRedirect()
            ->assertSessionHas('sukses');

        // Status request berubah menjadi disetujui
        $this->assertSame('disetujui', $pengajuan->refresh()->status);
        $this->assertSame($admin->id, $pengajuan->approved_by);
        $this->assertNotNull($pengajuan->approved_at);
        $this->assertNotNull($pengajuan->patient_id);

        // Data resmi masuk ke tabel patients
        $patient = Patient::find($pengajuan->patient_id);
        $this->assertNotNull($patient);
        $this->assertSame('Budi Santoso', $patient->nama_lengkap);
        $this->assertNotNull($patient->no_rm, 'No RM otomatis diterbitkan');
        $this->assertSame(36, strlen($patient->qr_token), 'QR token UUID otomatis diterbitkan');
        $this->assertTrue($patient->is_utama);

        // Setelah diapprove, pasien bisa melihat data di profil dan membuka kartu QR
        $this->actingAs($pasien)
            ->get(route('pasien.profil'))
            ->assertOk()
            ->assertSee($patient->no_rm)
            ->assertSee('Budi Santoso');

        $this->actingAs($pasien)
            ->get(route('pasien.qr'))
            ->assertOk()
            ->assertSee($patient->qr_token);
    }

    public function test_admin_reject_pengajuan_profil(): void
    {
        $pasien = $this->akunPasien();
        $admin = $this->akunAdmin();

        $pengajuan = PatientProfileRequest::create([
            'user_id' => $pasien->id,
            'nama_lengkap' => 'Data Fiktif',
            'hubungan_keluarga' => 'diri sendiri',
            'no_wa' => '081234567890',
            'tgl_lahir' => '1995-05-20',
            'jenis_kelamin' => 'L',
            'status' => 'menunggu',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.persetujuan-profil.reject', $pengajuan), [
                'catatan_admin' => 'Data tidak sesuai identitas',
            ])
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $this->assertSame('ditolak', $pengajuan->refresh()->status);
        $this->assertSame('Data tidak sesuai identitas', $pengajuan->catatan_admin);
        $this->assertDatabaseCount('patients', 0);
    }
}

