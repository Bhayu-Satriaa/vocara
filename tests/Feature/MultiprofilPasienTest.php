<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Multi-profile: satu akun pasien bisa memegang beberapa data diri (diri sendiri &
 * keluarga) dan menentukan mana yang dipakai untuk kartu QR dan booking.
 */
class MultiprofilPasienTest extends TestCase
{
    use RefreshDatabase;

    private function akunPasien(string $email = 'pasien@klinik.test'): User
    {
        /** @var \App\Models\User $user */
        return User::factory()->pasien()->create(['email' => $email]);
    }

    /** Akun pasien yang data dirinya sudah ada satu. */
    private function pasienDenganSatuProfil(array $ubah = []): array
    {
        $user = $this->akunPasien();

        $diri = Patient::query()->create(array_merge([
            'user_id' => $user->id,
            'no_rm' => 'RM-000001',
            'nama_lengkap' => 'Budi Santoso',
            'no_wa' => '081234567890',
            'tgl_lahir' => '1995-05-20',
            'jenis_kelamin' => 'L',
        ], $ubah));

        return [$user, $diri];
    }

    /** Isi form Tambah data diri. */
    private function dataDiriBaru(array $ubah = []): array
    {
        return array_merge([
            'nama_lengkap' => 'Sari Santoso',
            'hubungan_keluarga' => 'pasangan',
            'no_wa' => '081299998888',
            'tgl_lahir' => '1997-01-01',
            'jenis_kelamin' => 'P',
        ], $ubah);
    }

    public function test_data_diri_pertama_otomatis_jadi_profil_yang_dipakai(): void
    {
        $user = $this->akunPasien();
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($user)->put(route('pasien.profil.simpan'), [
            'nama_lengkap' => 'Budi Santoso',
            'hubungan_keluarga' => 'diri sendiri',
            'no_wa' => '081234567890',
            'tgl_lahir' => '1995-05-20',
            'jenis_kelamin' => 'L',
        ])->assertRedirect(route('pasien.profil'));

        $pengajuan = $user->profileRequests()->first();
        $this->assertNotNull($pengajuan);

        // Setelah disetujui admin
        $this->actingAs($admin)->post(route('admin.persetujuan-profil.approve', $pengajuan));

        $this->assertTrue($user->refresh()->profilAktif()?->is_utama, 'data diri pertama ditandai utama');
    }

    public function test_halaman_profil_menampilkan_semua_data_diri_dan_tandai_yang_dipakai(): void
    {
        [$user, $diri] = $this->pasienDenganSatuProfil();
        $keluarga = Patient::query()->create(array_merge(
            $this->dataDiriBaru(),
            ['user_id' => $user->id, 'no_rm' => 'RM-000002'],
        ));

        $this->actingAs($user)
            ->get(route('pasien.profil'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Sari Santoso')
            ->assertSee('dipakai')
            ->assertSee($diri->no_rm)
            ->assertSee($keluarga->no_rm)
            ->assertSee('Tambah data diri');
    }

    public function test_pasien_bisa_menambah_data_diri_kedua_tanpa_menggeser_profil_aktif(): void
    {
        [$user, $aktif] = $this->pasienDenganSatuProfil();
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($user)
            ->post(route('pasien.profil.tambah.simpan'), $this->dataDiriBaru())
            ->assertRedirect(route('pasien.profil'))
            ->assertSessionHas('sukses');

        $pengajuan = $user->profileRequests()->where('status', 'menunggu')->firstOrFail();

        // Disetujui admin
        $this->actingAs($admin)->post(route('admin.persetujuan-profil.approve', $pengajuan));

        $baru = $user->patients()->whereKeyNot($aktif->id)->firstOrFail();

        $this->assertSame('RM-000002', $baru->no_rm, 'nomor rekam medis baru dibuat otomatis');
        $this->assertSame(36, strlen($baru->qr_token), 'kartu QR ikut dibuat untuk data diri baru');
        $this->assertFalse($baru->is_utama);
        $this->assertTrue($aktif->refresh()->is_utama);
        $this->assertDatabaseHas('activity_logs', ['aksi' => 'setujui_profil']);
    }

    public function test_data_diri_baru_bisa_langsung_dijadikan_profil_aktif(): void
    {
        [$user, $aktif] = $this->pasienDenganSatuProfil();
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($user)
            ->post(route('pasien.profil.tambah.simpan'), $this->dataDiriBaru(['is_utama' => '1']))
            ->assertRedirect(route('pasien.profil'));

        $pengajuan = $user->profileRequests()->where('status', 'menunggu')->firstOrFail();

        // Disetujui admin
        $this->actingAs($admin)->post(route('admin.persetujuan-profil.approve', $pengajuan));

        $this->assertSame('Sari Santoso', $user->refresh()->profilAktif()->nama_lengkap);
        $this->assertFalse($aktif->refresh()->is_utama, 'profil lama dilepas');
        $this->assertSame(1, $user->patients()->where('is_utama', true)->count(), 'yang dipakai tetap satu');
    }

    public function test_pasien_bisa_memilih_data_diri_yang_dipakai(): void
    {
        [$user, $aktif] = $this->pasienDenganSatuProfil();
        $keluarga = Patient::query()->create(array_merge($this->dataDiriBaru(), [
            'user_id' => $user->id,
            'no_rm' => 'RM-000002',
        ]));

        $this->actingAs($user)
            ->post(route('pasien.profil.pakai', $keluarga))
            ->assertRedirect(route('pasien.profil'))
            ->assertSessionHas('sukses');

        $this->assertTrue($keluarga->refresh()->is_utama);
        $this->assertFalse($aktif->refresh()->is_utama);
        $this->assertSame($keluarga->id, $user->refresh()->profilAktif()->id);
        $this->assertDatabaseHas('activity_logs', ['aksi' => 'pilih_data_diri', 'subjek_id' => $keluarga->id]);

        // Kartu QR mengikuti data diri yang sedang dipakai.
        $this->actingAs($user)
            ->get(route('pasien.qr'))
            ->assertOk()
            ->assertSee($keluarga->qr_token)
            ->assertDontSee($aktif->qr_token)
            ->assertSee('pasangan');
    }

    public function test_form_tambah_dan_ubah_data_diri_bisa_dibuka(): void
    {
        [$user, $diri] = $this->pasienDenganSatuProfil();

        $this->actingAs($user)
            ->get(route('pasien.profil.tambah'))
            ->assertOk()
            ->assertSee('Tambah data diri')
            ->assertSee('-- pilih --');

        $this->actingAs($user)
            ->get(route('pasien.profil.ubah', $diri))
            ->assertOk()
            ->assertSee('Ubah data diri')
            ->assertSee($diri->no_rm);
    }

    public function test_data_diri_milik_akun_lain_ditolak(): void
    {
        [, $punyaOrangLain] = $this->pasienDenganSatuProfil();
        $akunSaya = $this->akunPasien('saya@klinik.test');

        $this->actingAs($akunSaya)->get(route('pasien.profil.ubah', $punyaOrangLain))->assertForbidden();

        $this->actingAs($akunSaya)
            ->put(route('pasien.profil.perbarui', $punyaOrangLain), $this->dataDiriBaru(['no_wa' => '081200002222']))
            ->assertForbidden();

        $this->actingAs($akunSaya)->post(route('pasien.profil.pakai', $punyaOrangLain))->assertForbidden();

        $this->assertTrue($punyaOrangLain->refresh()->is_utama, 'data diri akun lain tidak berubah');
    }

    public function test_hubungan_keluarga_wajib_dipilih(): void
    {
        [$user] = $this->pasienDenganSatuProfil();

        $this->actingAs($user)
            ->post(route('pasien.profil.tambah.simpan'), $this->dataDiriBaru(['hubungan_keluarga' => '']))
            ->assertSessionHasErrors('hubungan_keluarga');

        $this->assertDatabaseCount('patients', 1);
    }

    public function test_satu_data_diri_bisa_diubah_terpisah(): void
    {
        [$user, $aktif] = $this->pasienDenganSatuProfil();
        $keluarga = Patient::query()->create(array_merge($this->dataDiriBaru(), [
            'user_id' => $user->id,
            'no_rm' => 'RM-000002',
        ]));

        $this->actingAs($user)
            ->put(route('pasien.profil.perbarui', $keluarga), $this->dataDiriBaru([
                'no_wa' => '081200001111',
            ]))
            ->assertRedirect(route('pasien.profil'));

        $this->assertSame('081200001111', $keluarga->refresh()->no_wa);
        $this->assertTrue($aktif->refresh()->is_utama, 'mengubah data diri tidak memindahkan profil aktif');
        $this->assertSame('081234567890', $aktif->refresh()->no_wa, 'data diri yang lama tidak ikut berubah');
    }

    public function test_kolom_nik_sudah_tidak_ada_di_skema_patients(): void
    {
        $this->assertFalse(Schema::hasColumn('patients', 'nik'), 'kolom NIK sudah dihapus dari database');
    }

    public function test_dua_data_diri_dibedakan_nomor_rekam_medis(): void
    {
        [$user, $diri] = $this->pasienDenganSatuProfil();

        // Tanpa no_rm eksplisit: nomor rekam medis dibuat otomatis oleh model Patient.
        $keluarga = Patient::query()->create(array_merge($this->dataDiriBaru(), ['user_id' => $user->id]));

        $this->assertNotSame($diri->no_rm, $keluarga->no_rm);
        $this->assertSame('RM-000002', $keluarga->no_rm);

        $this->actingAs($user)
            ->get(route('pasien.profil'))
            ->assertOk()
            ->assertSee($diri->no_rm)
            ->assertSee($keluarga->no_rm);
    }

    public function test_halaman_profil_dan_form_tidak_lagi_menampilkan_nik(): void
    {
        [$user] = $this->pasienDenganSatuProfil();

        $this->actingAs($user)->get(route('pasien.profil'))->assertOk()->assertDontSee('NIK');
        $this->actingAs($user)->get(route('pasien.profil.tambah'))->assertOk()->assertDontSee('NIK');
    }

    public function test_halaman_hasil_scan_tidak_lagi_menampilkan_nik(): void
    {
        [, $pasien] = $this->pasienDenganSatuProfil();
        $petugas = User::factory()->role('petugas')->create();

        $this->actingAs($petugas)
            ->get(route('petugas.scan.hasil', $pasien))
            ->assertOk()
            ->assertDontSee('NIK')
            ->assertSee($pasien->no_rm)
            ->assertSee('Tanggal lahir');
    }
}
