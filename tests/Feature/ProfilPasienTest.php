<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilPasienTest extends TestCase
{
    use RefreshDatabase;

    /** Isi form Lengkapi Profil. */
    private function dataProfil(array $ubah = []): array
    {
        return array_merge([
            'nama_lengkap' => 'Budi Santoso',
            'hubungan_keluarga' => 'diri sendiri',
            'no_wa' => '081234567890',
            'tgl_lahir' => '1995-05-20',
            'jenis_kelamin' => 'L',
            'golongan_darah' => 'O',
            'alamat' => 'Jalan Contoh 1, Samarinda',
        ], $ubah);
    }

    private function pasien(bool $lengkap = false): User
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->pasien()->create(['email' => 'pasien@klinik.test']);

        if ($lengkap) {
            Patient::query()->create([
                'user_id' => $user->id,
                'no_rm' => 'RM-000001',
                'nama_lengkap' => 'Sudah Lengkap',
                'no_wa' => '081200000009',
                'tgl_lahir' => '1990-01-01',
                'jenis_kelamin' => 'L',
            ]);
        }

        return $user;
    }

    public function test_pasien_belum_lengkap_diarahkan_ke_lengkapi_profil(): void
    {
        $this->actingAs($this->pasien())
            ->get(route('pasien.qr'))
            ->assertRedirect(route('pasien.profil'))
            ->assertSessionHas('peringatan');
    }

    public function test_staf_tidak_bisa_membuka_halaman_pasien(): void
    {
        $petugas = User::factory()->role('petugas')->create();

        $this->actingAs($petugas)->get(route('pasien.profil'))->assertForbidden();
    }

    public function test_pasien_melengkapi_profil_dapat_nomor_rekam_medis_dan_token_qr(): void
    {
        $user = $this->pasien();
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($user)
            ->put(route('pasien.profil.simpan'), $this->dataProfil())
            ->assertRedirect(route('pasien.profil'))
            ->assertSessionHas('sukses');

        $this->assertNull($user->patients()->first(), 'belum masuk ke patients sebelum approve admin');

        $pengajuan = $user->profileRequests()->where('status', 'menunggu')->first();
        $this->assertNotNull($pengajuan);

        // Disetujui admin
        $this->actingAs($admin)->post(route('admin.persetujuan-profil.approve', $pengajuan));

        $pasien = $user->patients()->first();

        $this->assertNotNull($pasien);
        $this->assertSame('RM-000001', $pasien->no_rm);
        $this->assertSame(36, strlen($pasien->qr_token), 'token QR berupa UUID 36 karakter');
        $this->assertSame('belum', $pasien->verification_status);
        $this->assertDatabaseHas('activity_logs', ['aksi' => 'setujui_profil']);
    }

    public function test_nomor_wa_wajib_diisi(): void
    {
        $this->actingAs($this->pasien())
            ->put(route('pasien.profil.simpan'), $this->dataProfil(['no_wa' => '']))
            ->assertSessionHasErrors('no_wa');

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_perbaikan_profil_tidak_membuat_pasien_kedua(): void
    {
        $user = $this->pasien(true);

        $this->actingAs($user)
            ->put(route('pasien.profil.simpan'), $this->dataProfil([
                'no_wa' => '081200000999',
            ]))
            ->assertRedirect(route('pasien.profil'));

        $this->assertSame(1, $user->patients()->count());
        $this->assertSame('081200000999', $user->patients()->first()->no_wa);
    }

    public function test_kartu_qr_bisa_dibuka_setelah_profil_lengkap(): void
    {
        $user = $this->pasien(true);

        $this->actingAs($user)
            ->get(route('pasien.qr'))
            ->assertOk()
            ->assertSee($user->patients()->first()->qr_token)
            ->assertSee('Terdaftar');  // verification_status='belum' → badge "Terdaftar" (bukan "Terverifikasi KTP")
    }
}
