<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataPasienAdminTest extends TestCase
{
    use RefreshDatabase;

    private function pasien(string $nama, string $noWa = '081234567890'): Patient
    {
        return Patient::create([
            'nama_lengkap' => $nama,
            'no_wa' => $noWa,
            'tgl_lahir' => '1995-05-20',
            'jenis_kelamin' => 'L',
        ]);
    }

    public function test_admin_melihat_daftar_pasien(): void
    {
        $admin = User::factory()->role('admin')->create();
        $pasien = $this->pasien('Budi Santoso');

        $this->actingAs($admin)
            ->get(route('admin.pasien.index'))
            ->assertOk()
            ->assertSee('Data pasien')
            ->assertSee('Budi Santoso')
            ->assertSee($pasien->no_rm);
    }

    public function test_pencarian_menyaring_berdasar_nama_no_rm_dan_no_wa(): void
    {
        $admin = User::factory()->role('admin')->create();
        $budi = $this->pasien('Budi Santoso', '081111111111');
        $this->pasien('Sari Aminah', '082222222222');

        $this->actingAs($admin)
            ->get(route('admin.pasien.index', ['cari' => 'Budi']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Sari Aminah');

        $this->actingAs($admin)
            ->get(route('admin.pasien.index', ['cari' => $budi->no_rm]))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Sari Aminah');

        $this->actingAs($admin)
            ->get(route('admin.pasien.index', ['cari' => '082222222222']))
            ->assertOk()
            ->assertSee('Sari Aminah')
            ->assertDontSee('Budi Santoso');
    }

    public function test_admin_melihat_detail_data_diri_pasien(): void
    {
        $admin = User::factory()->role('admin')->create();
        $pasien = $this->pasien('Budi Santoso');

        $this->actingAs($admin)
            ->get(route('admin.pasien.show', $pasien))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee($pasien->no_rm)
            ->assertSee($pasien->alamat ?? '-');
    }

    public function test_kartu_walk_in_menampilkan_tombol_detail_untuk_admin(): void
    {
        $admin = User::factory()->role('admin')->create();
        $pasien = $this->pasien('Budi Santoso');

        $this->actingAs($admin)
            ->get(route('petugas.pasien.show', $pasien))
            ->assertOk()
            ->assertSee('Lihat Detail Data Diri')
            ->assertSee(route('admin.pasien.show', $pasien));
    }

    public function test_kartu_walk_in_tanpa_tombol_detail_untuk_petugas(): void
    {
        $petugas = User::factory()->role('petugas')->create();
        $pasien = $this->pasien('Budi Santoso');

        $this->actingAs($petugas)
            ->get(route('petugas.pasien.show', $pasien))
            ->assertOk()
            ->assertDontSee('Lihat Detail Data Diri');
    }

    public function test_filter_status_menyaring_berdasar_verifikasi(): void
    {
        $admin = User::factory()->role('admin')->create();
        $terverifikasi = $this->pasien('Budi Santoso', '081111111111');
        $terverifikasi->update(['verification_status' => 'terverifikasi']);
        $this->pasien('Sari Aminah', '082222222222');

        $this->actingAs($admin)
            ->get(route('admin.pasien.index', ['status' => 'terverifikasi']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Sari Aminah');

        $this->actingAs($admin)
            ->get(route('admin.pasien.index', ['status' => 'belum']))
            ->assertOk()
            ->assertSee('Sari Aminah')
            ->assertDontSee('Budi Santoso');

        $this->actingAs($admin)
            ->get(route('admin.pasien.index', ['status' => 'ngawur']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Sari Aminah');
    }

    public function test_selain_admin_tidak_boleh_membuka_data_pasien(): void
    {
        $petugas = User::factory()->role('petugas')->create();

        $this->actingAs($petugas)
            ->get(route('admin.pasien.index'))
            ->assertForbidden();
    }
}
