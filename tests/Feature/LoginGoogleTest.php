<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UserGoogle;
use Tests\TestCase;

class LoginGoogleTest extends TestCase
{
    use RefreshDatabase;

    /** Akun Google palsu untuk test (Socialite::fake, tidak butuh kredensial asli). */
    private function akunGoogle(array $ubah = []): UserGoogle
    {
        return (new UserGoogle())->map(array_merge([
            'id' => 'google-123',
            'name' => 'Pasien Google',
            'email' => 'pasien@gmail.test',
            'avatar' => 'https://contoh.test/foto.png',
        ], $ubah));
    }

    public function test_tanpa_kredensial_google_pesan_errornya_jelas(): void
    {
        config()->set('services.google.client_id', null);

        $this->get(route('google.arahkan'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_pasien_baru_dibuat_lalu_diarahkan_melengkapi_profil(): void
    {
        Socialite::fake('google', $this->akunGoogle());

        $this->get(route('google.balik'))->assertRedirect(route('pasien.profil'));

        $user = User::query()->where('email', 'pasien@gmail.test')->first();

        $this->assertNotNull($user, 'akun pasien baru dibuat otomatis');
        $this->assertSame('pasien', $user->role);
        $this->assertNull($user->password, 'pasien tidak memakai password');
        $this->assertSame('google-123', $user->google_id);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', ['aksi' => 'daftar_pasien']);
    }

    public function test_pasien_lama_dengan_profil_lengkap_langsung_ke_dashboard(): void
    {
        $user = User::factory()->pasien()->create([
            'email' => 'pasien@gmail.test',
            'google_id' => 'google-123',
        ]);
        Patient::query()->create([
            'user_id' => $user->id,
            'no_rm' => 'RM-000001',
            'nik' => '1234567890123456',
            'nama_lengkap' => 'Pasien Google',
            'no_wa' => '081200000000',
            'tgl_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
        ]);

        Socialite::fake('google', $this->akunGoogle());

        $this->get(route('google.balik'))->assertRedirect(route('dashboard'));
    }

    public function test_email_staf_tidak_bisa_masuk_lewat_google(): void
    {
        User::factory()->role('petugas')->create(['email' => 'petugas@klinik.test']);

        Socialite::fake('google', $this->akunGoogle(['email' => 'petugas@klinik.test']));

        $this->get(route('google.balik'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_nonaktif_ditolak_walau_login_google_berhasil(): void
    {
        User::factory()->pasien()->nonaktif()->create([
            'email' => 'pasien@gmail.test',
            'google_id' => 'google-123',
        ]);

        Socialite::fake('google', $this->akunGoogle());

        $this->get(route('google.balik'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
