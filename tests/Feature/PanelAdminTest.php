<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PanelAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_buka_panel_diarahkan_ke_login_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
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

    public function test_resource_akun_staf_hanya_untuk_admin(): void
    {
        $this->actingAs(User::factory()->role('petugas')->create())
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_akun_nonaktif_tidak_bisa_membuka_panel(): void
    {
        $admin = User::factory()->role('admin')->nonaktif()->create();

        $this->actingAs($admin)->get('/admin')->assertForbidden();
    }

    public function test_daftar_akun_staf_tidak_menampilkan_akun_pasien(): void
    {
        $admin = User::factory()->role('admin')->create();
        User::factory()->role('dokter')->create(['name' => 'dr. Uji Panel']);
        User::factory()->pasien()->create(['name' => 'Pasien Uji Panel']);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('dr. Uji Panel')
            ->assertDontSee('Pasien Uji Panel');
    }

    public function test_admin_bisa_membuat_akun_staf_dari_panel_dan_tercatat_di_log(): void
    {
        $admin = User::factory()->role('admin')->create();
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Petugas Baru',
                'email' => 'petugas.baru@klinik.test',
                'role' => 'petugas',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $baru = User::query()->where('email', 'petugas.baru@klinik.test')->first();

        $this->assertNotNull($baru, 'akun staf baru tersimpan');
        $this->assertSame('petugas', $baru->role);
        $this->assertTrue(Hash::check('rahasia123', $baru->password), 'password disimpan sebagai hash');
        $this->assertDatabaseHas('activity_logs', ['aksi' => 'tambah_akun', 'user_id' => $admin->id]);
    }

    public function test_email_duplikat_ditolak_di_form_panel(): void
    {
        $admin = User::factory()->role('admin')->create();
        User::factory()->create(['email' => 'sudah.ada@klinik.test']);
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Ganda',
                'email' => 'sudah.ada@klinik.test',
                'role' => 'petugas',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    public function test_password_harus_dikonfirmasi_dan_minimal_8_karakter(): void
    {
        $admin = User::factory()->role('admin')->create();
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Tanpa Konfirmasi',
                'email' => 'tanpa.konfirmasi@klinik.test',
                'role' => 'dokter',
                'password' => 'rahasia123',
                'password_confirmation' => 'beda-sekali',
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Password Pendek',
                'email' => 'pendek@klinik.test',
                'role' => 'dokter',
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);
    }
}
