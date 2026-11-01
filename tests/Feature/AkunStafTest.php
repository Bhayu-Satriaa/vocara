<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Kelola akun staf di dashboard admin — pindahan dari panel Filament.
 *
 * Penamaan: file `AkunStafTest`, method `test_<keadaan>_<harapan>`, dan email
 * data uji berpola `<role>.<keperluan>@klinik.test`.
 */
class AkunStafTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('admin.akun-staf.index'))->assertRedirect(route('login'));
    }

    public function test_selain_admin_tidak_bisa_membuka_daftar_akun_staf(): void
    {
        $this->actingAs(User::factory()->role('petugas')->create())
            ->get(route('admin.akun-staf.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->role('dokter')->create())
            ->get(route('admin.akun-staf.create'))
            ->assertForbidden();
    }

    public function test_daftar_menampilkan_akun_staf_tanpa_akun_pasien(): void
    {
        $admin = User::factory()->role('admin')->create();
        User::factory()->role('dokter')->create(['name' => 'dr. Uji Staf']);
        User::factory()->pasien()->create(['name' => 'Pasien Uji Staf']);

        $this->actingAs($admin)
            ->get(route('admin.akun-staf.index'))
            ->assertOk()
            ->assertSee('dr. Uji Staf')
            ->assertDontSee('Pasien Uji Staf');
    }

    public function test_filter_role_menyaring_daftar(): void
    {
        $admin = User::factory()->role('admin')->create();
        User::factory()->role('dokter')->create(['name' => 'Dokter Tampil']);
        User::factory()->role('apoteker')->create(['name' => 'Apoteker Tersaring']);

        $this->actingAs($admin)
            ->get(route('admin.akun-staf.index', ['role' => 'dokter']))
            ->assertOk()
            ->assertSee('Dokter Tampil')
            ->assertDontSee('Apoteker Tersaring');
    }

    public function test_admin_menambah_akun_staf_dan_tercatat_di_log(): void
    {
        $admin = User::factory()->role('admin')->create();
        $this->actingAs($admin);

        $this->post(route('admin.akun-staf.store'), [
            'name' => 'Petugas Baru',
            'email' => 'petugas.baru@klinik.test',
            'role' => 'petugas',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'is_active' => '1',
        ])->assertRedirect(route('admin.akun-staf.index'));

        $baru = User::query()->where('email', 'petugas.baru@klinik.test')->first();

        $this->assertNotNull($baru, 'akun staf baru tersimpan');
        $this->assertSame('petugas', $baru->role);
        $this->assertTrue($baru->is_active);
        $this->assertTrue(Hash::check('rahasia123', $baru->password), 'password disimpan sebagai hash');
        $this->assertDatabaseHas('activity_logs', ['aksi' => 'tambah_akun', 'user_id' => $admin->id]);
    }

    public function test_akun_baru_bisa_disimpan_sebagai_nonaktif(): void
    {
        $this->actingAs(User::factory()->role('admin')->create());

        $this->post(route('admin.akun-staf.store'), [
            'name' => 'Petugas Nonaktif',
            'email' => 'petugas.nonaktif@klinik.test',
            'role' => 'petugas',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'is_active' => '0',
        ]);

        $this->assertFalse(
            User::query()->where('email', 'petugas.nonaktif@klinik.test')->first()->is_active
        );
    }

    public function test_email_duplikat_ditolak(): void
    {
        $this->actingAs(User::factory()->role('admin')->create());
        User::factory()->create(['email' => 'petugas.duplikat@klinik.test']);

        $this->post(route('admin.akun-staf.store'), [
            'name' => 'Petugas Ganda',
            'email' => 'petugas.duplikat@klinik.test',
            'role' => 'petugas',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');
    }

    public function test_password_harus_dikonfirmasi_dan_minimal_8_karakter(): void
    {
        $this->actingAs(User::factory()->role('admin')->create());

        $this->post(route('admin.akun-staf.store'), [
            'name' => 'Dokter Password Beda',
            'email' => 'dokter.password.beda@klinik.test',
            'role' => 'dokter',
            'password' => 'rahasia123',
            'password_confirmation' => 'beda-sekali',
        ])->assertSessionHasErrors('password');

        $this->post(route('admin.akun-staf.store'), [
            'name' => 'Dokter Password Pendek',
            'email' => 'dokter.password.pendek@klinik.test',
            'role' => 'dokter',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'dokter.password.pendek@klinik.test']);
    }

    public function test_role_di_luar_staf_ditolak(): void
    {
        $this->actingAs(User::factory()->role('admin')->create());

        $this->post(route('admin.akun-staf.store'), [
            'name' => 'Calon Pasien',
            'email' => 'calon.pasien@klinik.test',
            'role' => 'pasien',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'calon.pasien@klinik.test']);
    }

    public function test_ubah_akun_tanpa_password_mempertahankan_password_lama(): void
    {
        $this->actingAs(User::factory()->role('admin')->create());
        $staf = User::factory()->role('dokter')->create([
            'email' => 'dokter.lama@klinik.test',
            'password' => 'rahasia-lama',
        ]);

        $this->put(route('admin.akun-staf.update', $staf), [
            'name' => 'Nama Baru',
            'email' => 'dokter.lama@klinik.test',
            'role' => 'dokter',
            'password' => '',
            'is_active' => '1',
        ])->assertRedirect(route('admin.akun-staf.index'));

        $staf->refresh();

        $this->assertSame('Nama Baru', $staf->name);
        $this->assertTrue(Hash::check('rahasia-lama', $staf->password), 'password lama tetap dipakai');
        $this->assertDatabaseHas('activity_logs', ['aksi' => 'ubah_akun']);
    }

    public function test_ubah_akun_dengan_password_baru(): void
    {
        $this->actingAs(User::factory()->role('admin')->create());
        $staf = User::factory()->role('apoteker')->create();

        $this->put(route('admin.akun-staf.update', $staf), [
            'name' => $staf->name,
            'email' => $staf->email,
            'role' => 'apoteker',
            'password' => 'rahasia-baru',
            'password_confirmation' => 'rahasia-baru',
            'is_active' => '1',
        ]);

        $this->assertTrue(Hash::check('rahasia-baru', $staf->refresh()->password));
    }

    public function test_tombol_aktif_nonaktif_mengubah_status_akun(): void
    {
        $admin = User::factory()->role('admin')->create();
        $staf = User::factory()->role('petugas')->create(['is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.akun-staf.toggle-aktif', $staf))
            ->assertRedirect();

        $this->assertFalse($staf->refresh()->is_active);

        $this->post(route('admin.akun-staf.toggle-aktif', $staf));

        $this->assertTrue($staf->refresh()->is_active);
    }

    public function test_akun_pasien_tidak_bisa_disentuh_lewat_rute_ini(): void
    {
        $this->actingAs(User::factory()->role('admin')->create());
        $pasien = User::factory()->pasien()->create();

        $this->get(route('admin.akun-staf.edit', $pasien))->assertNotFound();
        $this->put(route('admin.akun-staf.update', $pasien), [
            'name' => 'Diubah',
            'email' => $pasien->email,
            'role' => 'petugas',
            'is_active' => '1',
        ])->assertNotFound();
        $this->post(route('admin.akun-staf.toggle-aktif', $pasien))->assertNotFound();
    }

    public function test_admin_bisa_membuka_form_tambah_dan_form_ubah(): void
    {
        $admin = User::factory()->role('admin')->create();
        $staf = User::factory()->role('petugas')->create(['name' => 'Petugas Form']);

        $this->actingAs($admin)
            ->get(route('admin.akun-staf.create'))
            ->assertOk()
            ->assertSee('Tambah akun staf');

        $this->actingAs($admin)
            ->get(route('admin.akun-staf.edit', $staf))
            ->assertOk()
            ->assertSee('Petugas Form');
    }

    public function test_halaman_panel_admin_memuat_pintasan_akun_staf(): void
    {
        $this->actingAs(User::factory()->role('admin')->create())
            ->get(route('admin.panel-admin.index'))
            ->assertOk()
            ->assertSee('Panel admin')
            ->assertSee('href="'.route('admin.akun-staf.index').'"', false);
    }

    public function test_selain_admin_tidak_bisa_membuka_panel_admin(): void
    {
        $this->actingAs(User::factory()->role('dokter')->create())
            ->get(route('admin.panel-admin.index'))
            ->assertForbidden();
    }

    public function test_dashboard_admin_tidak_punya_tab_panel_admin(): void
    {
        $this->actingAs(User::factory()->role('admin')->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('?tab=panel-admin');
    }

    /** Regresi: menu sidebar menunjuk halaman panel admin, bukan panel Filament default (/admin). */
    public function test_menu_panel_admin_mengarah_ke_halaman_panel_admin(): void
    {
        $this->actingAs(User::factory()->role('admin')->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.panel-admin.index').'"', false)
            ->assertDontSee('href="'.url('/admin').'"', false);
    }
}
