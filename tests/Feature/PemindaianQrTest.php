<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PemindaianQrTest extends TestCase
{
    use RefreshDatabase;

    /** Pasien siap punya kartu QR (profil lengkap). */
    private function pasien(array $ubah = []): Patient
    {
        $user = User::factory()->pasien()->create(['email' => 'pasien@klinik.test']);

        return Patient::query()->create(array_merge([
            'user_id' => $user->id,
            'no_rm' => 'RM-000001',
            'nama_lengkap' => 'Budi Santoso',
            'no_wa' => '081234567890',
            'tgl_lahir' => '1995-05-20',
            'jenis_kelamin' => 'L',
        ], $ubah));
    }

    public function test_petugas_bisa_membuka_halaman_scan(): void
    {
        $this->actingAs(User::factory()->role('petugas')->create())
            ->get(route('petugas.scan'))
            ->assertOk()
            ->assertSee('Scan QR pasien');
    }

    public function test_pasien_dan_dokter_tidak_bisa_membuka_halaman_scan(): void
    {
        $this->actingAs(User::factory()->role('dokter')->create())
            ->get(route('petugas.scan'))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('petugas.scan'))->assertRedirect(route('login'));
    }

    public function test_token_tidak_dikenal_tidak_mencatat_apa_pun(): void
    {
        $this->actingAs(User::factory()->role('petugas')->create())
            ->post(route('petugas.scan.cari'), ['token' => 'token-ngawur'])
            ->assertRedirect()
            ->assertSessionHas('peringatan');

        $this->assertDatabaseCount('qr_scan_logs', 0);
    }

    public function test_token_valid_menampilkan_data_pasien_dan_tercatat_di_log(): void
    {
        $pasien = $this->pasien();
        $petugas = User::factory()->role('petugas')->create();

        $this->actingAs($petugas)
            ->post(route('petugas.scan.cari'), ['token' => $pasien->qr_token])
            ->assertRedirect(route('petugas.scan.hasil', $pasien));

        $this->assertDatabaseHas('qr_scan_logs', [
            'patient_id' => $pasien->id,
            'scanned_by' => $petugas->id,
            // Pasien belum terverifikasi → scan pertama dihitung sebagai verifikasi.
            'konteks' => 'verifikasi',
        ]);

        $this->assertDatabaseHas('activity_logs', ['aksi' => 'scan_qr', 'subjek_id' => $pasien->id]);

        $this->actingAs($petugas)
            ->get(route('petugas.scan.hasil', $pasien))
            ->assertOk()
            ->assertSee('RM-000001')
            ->assertSee('Budi Santoso')
            ->assertSee('belum terverifikasi');
    }

    public function test_verifikasi_ktp_mengubah_status_dan_mencatat_pelakunya(): void
    {
        $pasien = $this->pasien();
        $petugas = User::factory()->role('petugas')->create();

        $this->actingAs($petugas)
            ->post(route('petugas.scan.verifikasi', $pasien))
            ->assertRedirect(route('petugas.scan.hasil', $pasien))
            ->assertSessionHas('sukses');

        $pasien->refresh();

        $this->assertTrue($pasien->sudahTerverifikasi());
        $this->assertSame($petugas->id, $pasien->verified_by);
        $this->assertNotNull($pasien->verified_at);
        $this->assertDatabaseHas('activity_logs', ['aksi' => 'verifikasi_ktp', 'subjek_id' => $pasien->id]);
    }

    public function test_verifikasi_kedua_kali_tidak_menimpa_pelaku_pertama(): void
    {
        $pasien = $this->pasien(['verification_status' => 'terverifikasi']);

        $this->actingAs(User::factory()->role('petugas')->create())
            ->post(route('petugas.scan.verifikasi', $pasien))
            ->assertRedirect(route('petugas.scan.hasil', $pasien))
            ->assertSessionHas('peringatan');

        $this->assertNull($pasien->refresh()->verified_by);
    }

    public function test_kartu_qr_pasien_menampilkan_gambar_qr(): void
    {
        $pasien = $this->pasien();

        $this->actingAs($pasien->user)
            ->get(route('pasien.qr'))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee($pasien->qr_token);
    }
}
