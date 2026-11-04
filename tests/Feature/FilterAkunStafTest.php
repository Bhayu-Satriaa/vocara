<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterAkunStafTest extends TestCase
{
    use RefreshDatabase;

    public function test_tab_role_menyaring_daftar_akun_staf(): void
    {
        $admin = User::factory()->role('admin')->create(['name' => 'Admin Satu']);
        User::factory()->role('dokter')->create(['name' => 'Dokter Dua']);
        User::factory()->role('apoteker')->create(['name' => 'Apoteker Tiga']);
        User::factory()->role('petugas')->create(['name' => 'Petugas Empat']);
        User::factory()->pasien()->create(['name' => 'Pasien Lima']);

        $this->actingAs($admin)
            ->get(route('admin.akun-staf.index', ['role' => 'dokter']))
            ->assertOk()
            ->assertSee('Dokter Dua')
            ->assertDontSee('Apoteker Tiga')
            ->assertDontSee('Petugas Empat')
            ->assertDontSee('Pasien Lima');

        $this->actingAs($admin)
            ->get(route('admin.akun-staf.index'))
            ->assertOk()
            ->assertSee('Dokter Dua')
            ->assertSee('Apoteker Tiga')
            ->assertSee('Petugas Empat')
            ->assertDontSee('Pasien Lima');
    }

    public function test_sidebar_menampilkan_pemisah_grup(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)
            ->get(route('admin.akun-staf.index'))
            ->assertOk()
            ->assertSee('Pendaftaran')
            ->assertSee('Kelola');
    }
}
