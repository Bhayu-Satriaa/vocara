<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Poli;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AnalitikDemoSeeder extends Seeder
{
    /**
     * shortcut: data acak, tidak realistis secara medis, HAPUS sebelum production.
     * Jalankan: php artisan db:seed --class=AnalitikDemoSeeder
     * Kembalikan: php artisan migrate:fresh --seed (atau hapus baris visits hasil seeder ini)
     */
    public function run(): void
    {
        $dokter = Doctor::query()->with('poli')->get();

        if ($dokter->isEmpty()) {
            $this->command?->warn('Tidak ada dokter. Jalankan MasterDataSeeder dulu.');

            return;
        }

        srand(42); // hasil sama setiap kali dijalankan ulang

        $poliUmum = Poli::query()->where('nama', 'Poli Umum')->first();
        $bobotPoli = $dokter->groupBy('poli_id')->mapWithKeys(fn ($grup, $poliId) => [
            $poliId => $poliId === $poliUmum?->id ? 5 : 2,
        ]);

        for ($mundur = 29; $mundur >= 0; $mundur--) {
            $tanggal = today()->subDays($mundur);
            $hariKe = 29 - $mundur;

            // Pola miring naik + ramai di Senin + sepi di Minggu, supaya grafik terlihat hidup.
            $jumlah = (int) round(3 + $hariKe * 0.35
                + ($tanggal->dayOfWeekIso === 1 ? 6 : 0)
                - ($tanggal->dayOfWeekIso === 7 ? 2 : 0));

            for ($i = 0; $i < max($jumlah, 1); $i++) {
                $terpilih = $dokter->random();

                // Poli Umum dibuat paling ramai secara proporsional.
                if ($poliUmum && rand(1, 7) <= $bobotPoli->get($terpilih->poli_id, 2)) {
                    $terpilih = $dokter->firstWhere('poli_id', $poliUmum->id) ?? $terpilih;
                }

                $pasien = Patient::create([
                    'nama_lengkap' => 'Pasien Demo '.$tanggal->format('d').'-'.$i,
                    'no_wa' => '0812'.str_pad((string) rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                    'tgl_lahir' => Carbon::now()->subYears(rand(5, 70))->format('Y-m-d'),
                    'jenis_kelamin' => rand(0, 1) ? 'L' : 'P',
                ]);

                // Slot jam acak: coba ulang kalau tabrakan unique anti double-booking.
                for ($coba = 0; $coba < 10; $coba++) {
                    try {
                        Visit::create([
                            'patient_id' => $pasien->id,
                            'doctor_id' => $terpilih->id,
                            'poli_id' => $terpilih->poli_id,
                            'tgl_kunjungan' => $tanggal->toDateString(),
                            'jam' => sprintf('%02d:%02d', rand(8, 15), rand(0, 59)),
                            'status' => $tanggal->isToday() ? 'menunggu' : 'selesai',
                        ]);
                        break;
                    } catch (\Illuminate\Database\QueryException $e) {
                        if ($coba === 9) {
                            throw $e;
                        }
                    }
                }
            }
        }

        $this->command?->info('Seeder demo analitik selesai: ~30 hari kunjungan dibuat.');
    }
}
