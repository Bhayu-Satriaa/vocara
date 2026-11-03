<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Poli;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalitikDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function kunjungan(Poli $poli, string $tanggal): void
    {
        $dokter = Doctor::create([
            'user_id' => User::factory()->role('dokter')->create()->id,
            'poli_id' => $poli->id,
        ]);
        $pasien = Patient::create([
            'nama_lengkap' => 'Pasien Tes',
            'no_wa' => '081234567890',
            'tgl_lahir' => '1995-05-20',
            'jenis_kelamin' => 'L',
        ]);

        Visit::create([
            'patient_id' => $pasien->id,
            'doctor_id' => $dokter->id,
            'poli_id' => $poli->id,
            'tgl_kunjungan' => $tanggal,
            'jam' => '08:00',
        ]);
    }

    public function test_dashboard_admin_memuat_grafik_kunjungan_dan_poli(): void
    {
        $admin = User::factory()->role('admin')->create();
        $poli = Poli::create(['nama' => 'Umum']);
        $this->kunjungan($poli, today()->toDateString());
        $this->kunjungan($poli, today()->subMonths(2)->toDateString()); // di luar bulan berjalan

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Analitik kunjungan')
            ->assertSee('grafik-kunjungan')
            ->assertSee('grafik-poli')
            ->assertSee('Total kunjungan')
            ->assertSee('Pasien terdaftar')
            ->assertSee('linear-gradient')
            ->assertSee('Umum');
    }

    public function test_analitik_bisa_dipilih_per_bulan(): void
    {
        $admin = User::factory()->role('admin')->create();
        $poli = Poli::create(['nama' => 'Umum']);
        $this->kunjungan($poli, '2026-10-05');

        $this->actingAs($admin)
            ->get(route('dashboard', ['bulan' => '2026-10']))
            ->assertOk()
            ->assertSee('Oktober 2026')
            ->assertSee('Total kunjungan');
    }

            public function test_dashboard_non_admin_tidak_memuat_analitik(): void
    {
        $petugas = User::factory()->role('petugas')->create();

        $this->actingAs($petugas)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('grafik-kunjungan');
    }
}
