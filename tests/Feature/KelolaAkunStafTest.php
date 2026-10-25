<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KelolaAkunStafTest extends TestCase
{
    use RefreshDatabase;

    /** Data form pembuatan akun staf. */
    private function dataStaf(array $ubah = []): array
    {
        return array_merge([
            'name' => 'Petugas Baru',
            'email' => 'petugas.baru@klinik.test',
            'role' => 'petugas',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $ubah);
    }

    public function test_hanya_admin_yang_boleh_membuka_kelola_akun(): void
    {
        $petugas = User::factory()->role('petugas')->create();

        $this->actingAs($petugas)->get(route('admin.pengguna'))->assertForbidden();
    }

    public function test_admin_melihat_daftar_akun_staf_tanpa_akun_pasien(): void
    {
        $admin = User::factory()->role('admin')->create();
        User::factory()->role('dokter')->create(['name' => 'dr. Uji Relasi']);
        User::factory()->pasien()->create(['name' => 'Pasien Uji Relasi']);

        $this->actingAs($admin)
            ->get(route('admin.pengguna'))
            ->assertOk()
            ->assertSee('dr. Uji Relasi')
            ->assertDontSee('Pasien Uji Relasi');
    }

    public function test_admin_bisa_membuat_akun_staf_dan_aktivitasnya_tercatat(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)
            ->post(route('admin.pengguna.simpan'), $this->dataStaf())
            ->assertRedirect(route('admin.pengguna'));

        $baru = User::query()->where('email', 'petugas.baru@klinik.test')->first();

        $this->assertNotNull($baru, 'akun staf baru tersimpan');
        $this->assertSame('petugas', $baru->role);
        $this->assertTrue(Hash::check('rahasia123', $baru->password), 'password disimpan sebagai hash');
        $this->assertDatabaseHas('activity_logs', ['aksi' => 'tambah_akun', 'user_id' => $admin->id]);
    }

    public function test_email_duplikat_ditolak(): void
    {
        $admin = User::factory()->role('admin')->create();
        User::factory()->create(['email' => 'sudah.ada@klinik.test']);

        $this->actingAs($admin)
            ->post(route('admin.pengguna.simpan'), $this->dataStaf(['email' => 'sudah.ada@klinik.test']))
            ->assertSessionHasErrors('email');
    }

    public function test_role_pasien_tidak_boleh_dibuat_dari_panel(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)
            ->post(route('admin.pengguna.simpan'), $this->dataStaf(['role' => 'pasien']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'petugas.baru@klinik.test']);
    }

    public function test_password_harus_dikonfirmasi_dan_minimal_8_karakter(): void
    {
        $admin = User::factory()->role('admin')->create();

        $tanpaKonfirmasi = $this->dataStaf(['password_confirmation' => '']);
        $this->actingAs($admin)
            ->post(route('admin.pengguna.simpan'), $tanpaKonfirmasi)
            ->assertSessionHasErrors('password');

        $terlaluPendek = $this->dataStaf(['password' => 'pendek', 'password_confirmation' => 'pendek']);
        $this->actingAs($admin)
            ->post(route('admin.pengguna.simpan'), $terlaluPendek)
            ->assertSessionHasErrors('password');
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('admin.pengguna'))->assertRedirect(route('login'));
    }
}
