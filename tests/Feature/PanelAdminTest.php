<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_buka_panel_diarahkan_ke_login_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_logout_panel_diarahkan_ke_login_web(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)->post('/admin/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_admin_bisa_membuka_panel(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_petugas_bisa_membuka_panel(): void
    {
        $this->actingAs(User::factory()->role('petugas')->create())->get('/admin')->assertOk();
    }

    public function test_dokter_bisa_membuka_panel(): void
    {
        $this->actingAs(User::factory()->role('dokter')->create())->get('/admin')->assertOk();
    }

    public function test_apoteker_bisa_membuka_panel(): void
    {
        $this->actingAs(User::factory()->role('apoteker')->create())->get('/admin')->assertOk();
    }

    public function test_pasien_tidak_bisa_membuka_panel(): void
    {
        // Pasien dibuat dengan password supaya sesi tidak dibatalkan middleware
        // AuthenticateSession Filament — yang diuji di sini aturan aksesnya (403).
        $this->actingAs(User::factory()->pasien()->create(['password' => 'rahasia123']))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_akun_nonaktif_tidak_bisa_membuka_panel(): void
    {
        $admin = User::factory()->role('admin')->nonaktif()->create();

        $this->actingAs($admin)->get('/admin')->assertForbidden();
    }

    /** Regresi: kelola akun staf pindah ke dashboard admin, bukan di panel Filament. */
    public function test_resource_akun_staf_filament_sudah_dihapus_dari_panel(): void
    {
        $this->actingAs(User::factory()->role('admin')->create())
            ->get('/admin/users')
            ->assertNotFound();
    }
}
