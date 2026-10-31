<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginStafTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_tampil_untuk_tamu(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk untuk staf klinik');
    }

    public function test_staf_bisa_masuk_lalu_diarahkan_ke_dashboard(): void
    {
        $petugas = User::factory()->role('petugas')->create(['email' => 'petugas@klinik.test']);

        $this->post(route('login.masuk'), [
            'email' => 'petugas@klinik.test',
            'password' => UserFactory::PASSWORD,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($petugas);
        $this->get(route('dashboard'))->assertOk()->assertSee('Dashboard petugas');
    }

    public function test_admin_diarahkan_ke_dashboard_dan_panel_filament_tetap_bisa_dibuka(): void
    {
        User::factory()->role('admin')->create(['email' => 'admin@klinik.test']);

        $this->post(route('login.masuk'), [
            'email' => 'admin@klinik.test',
            'password' => UserFactory::PASSWORD,
        ])->assertRedirect(route('dashboard'));

        // Regresi: dulu halaman ini 500 karena view memanggil route('admin.pengguna') yang sudah dihapus.
        $this->get(route('dashboard'))->assertOk()->assertSee('Dashboard admin');
        $this->get('/admin')->assertOk();
    }

    public function test_password_salah_ditolak(): void
    {
        User::factory()->role('petugas')->create(['email' => 'petugas@klinik.test']);

        $this->from(route('login'))
            ->post(route('login.masuk'), ['email' => 'petugas@klinik.test', 'password' => 'jelas-salah'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_pasien_tidak_bisa_masuk_lewat_halaman_staf(): void
    {
        User::factory()->pasien()->create(['email' => 'pasien@klinik.test', 'password' => 'rahasia123']);

        $this->post(route('login.masuk'), ['email' => 'pasien@klinik.test', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_nonaktif_ditolak(): void
    {
        User::factory()->role('dokter')->nonaktif()->create(['email' => 'dokter@klinik.test']);

        $this->post(route('login.masuk'), [
            'email' => 'dokter@klinik.test',
            'password' => UserFactory::PASSWORD,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_staf_bisa_keluar(): void
    {
        $user = User::factory()->role('apoteker')->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
