<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalDokterTest extends TestCase
{
    use RefreshDatabase;

    private function dokter(): Doctor
    {
        return Doctor::create([
            'user_id' => User::factory()->role('dokter')->create()->id,
            'poli_id' => Poli::create(['nama' => 'Umum'])->id,
        ]);
    }

    public function test_admin_melihat_daftar_dokter_dan_form_jadwal(): void
    {
        $admin = User::factory()->role('admin')->create();
        $dokter = $this->dokter();
        $dokter->schedules()->create([
            'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00',
            'durasi_slot' => 30, 'kuota' => 8,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.jadwal-dokter.index'))
            ->assertOk()
            ->assertSee('Atur jadwal');

        $this->actingAs($admin)
            ->get(route('admin.jadwal-dokter.show', $dokter))
            ->assertOk()
            ->assertSee('Senin')
            ->assertSee('Tambah hari praktik');
    }

    public function test_admin_tambah_ubah_hapus_jadwal(): void
    {
        $admin = User::factory()->role('admin')->create();
        $dokter = $this->dokter();

        $this->actingAs($admin)
            ->post(route('admin.jadwal-dokter.store', $dokter), [
                'hari' => 'Selasa', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00',
                'durasi_slot' => 30, 'kuota' => 8, 'is_active' => true,
            ])
            ->assertRedirect(route('admin.jadwal-dokter.show', $dokter));

        $jadwal = DoctorSchedule::where('doctor_id', $dokter->id)->where('hari', 'Selasa')->first();
        $this->assertNotNull($jadwal);

        $this->actingAs($admin)
            ->put(route('admin.jadwal-dokter.update', [$dokter, $jadwal]), [
                'hari' => 'Selasa', 'jam_mulai' => '13:00', 'jam_selesai' => '16:00',
                'durasi_slot' => 20, 'kuota' => 10, 'is_active' => false,
            ])
            ->assertRedirect(route('admin.jadwal-dokter.show', $dokter));

        $this->assertSame('13:00', substr($jadwal->refresh()->jam_mulai, 0, 5));
        $this->assertFalse($jadwal->is_active);

        $this->actingAs($admin)
            ->delete(route('admin.jadwal-dokter.destroy', [$dokter, $jadwal]))
            ->assertRedirect(route('admin.jadwal-dokter.show', $dokter));

        $this->assertDatabaseMissing('doctor_schedules', ['id' => $jadwal->id]);
    }

    public function test_duplikat_hari_ditolak_dan_jam_selesai_harus_setelah_mulai(): void
    {
        $admin = User::factory()->role('admin')->create();
        $dokter = $this->dokter();
        $dokter->schedules()->create([
            'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00',
            'durasi_slot' => 30, 'kuota' => 8,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.jadwal-dokter.store', $dokter), [
                'hari' => 'Senin', 'jam_mulai' => '13:00', 'jam_selesai' => '12:00',
                'durasi_slot' => 30, 'kuota' => 8,
            ])
            ->assertSessionHasErrors(['hari', 'jam_selesai']);

        $this->assertSame(1, DoctorSchedule::where('doctor_id', $dokter->id)->count());
    }

    public function test_selain_admin_tidak_boleh_membuka_jadwal_dokter(): void
    {
        $petugas = User::factory()->role('petugas')->create();
        $dokter = $this->dokter();

        $this->actingAs($petugas)
            ->get(route('admin.jadwal-dokter.index'))
            ->assertForbidden();

        $this->actingAs($petugas)
            ->get(route('admin.jadwal-dokter.show', $dokter))
            ->assertForbidden();
    }
}
