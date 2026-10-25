<?php

namespace Database\Seeders;

use App\Models\Disease;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Medicine;
use App\Models\Poli;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /** Data master awal: poli, dokter + jadwal, layanan, obat, dan ICD-10. */
    public function run(): void
    {
        $polis = collect([
            ['nama' => 'Poli Umum', 'deskripsi' => 'Pemeriksaan umum dan konsultasi awal'],
            ['nama' => 'Poli Gigi', 'deskripsi' => 'Perawatan dan pemeriksaan gigi'],
            ['nama' => 'Poli Anak', 'deskripsi' => 'Pemeriksaan kesehatan anak'],
        ])->mapWithKeys(fn (array $data) => [
            $data['nama'] => Poli::query()->updateOrCreate(['nama' => $data['nama']], $data),
        ]);

        $dokter = [
            ['nama' => 'dr. Ayu Lestari', 'email' => 'dokter.ayu@klinik.test', 'poli' => 'Poli Umum', 'no_str' => 'STR-0001', 'spesialisasi' => 'Dokter Umum', 'tarif' => 25000],
            ['nama' => 'drg. Beni Saputra', 'email' => 'dokter.beni@klinik.test', 'poli' => 'Poli Gigi', 'no_str' => 'STR-0002', 'spesialisasi' => 'Dokter Gigi', 'tarif' => 35000],
            ['nama' => 'dr. Citra Maharani', 'email' => 'dokter.citra@klinik.test', 'poli' => 'Poli Anak', 'no_str' => 'STR-0003', 'spesialisasi' => 'Spesialis Anak', 'tarif' => 40000],
        ];

        foreach ($dokter as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['nama'], 'password' => 'password', 'role' => 'dokter', 'is_active' => true],
            );

            $doctor = Doctor::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'poli_id' => $polis[$data['poli']]->id,
                    'no_str' => $data['no_str'],
                    'spesialisasi' => $data['spesialisasi'],
                    'tarif_konsultasi' => $data['tarif'],
                ],
            );

            // Jadwal praktik Senin–Jumat, 08:00–12:00, satu slot 30 menit.
            foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari) {
                DoctorSchedule::query()->updateOrCreate(
                    ['doctor_id' => $doctor->id, 'hari' => $hari],
                    [
                        'jam_mulai' => '08:00:00',
                        'jam_selesai' => '12:00:00',
                        'durasi_slot' => 30,
                        'kuota' => 8,
                        'is_active' => true,
                    ],
                );
            }
        }

        $layanan = [
            ['nama' => 'Konsultasi Dokter', 'kategori' => 'konsultasi', 'tarif' => 25000],
            ['nama' => 'Pemeriksaan Tekanan Darah', 'kategori' => 'pemeriksaan', 'tarif' => 10000],
            ['nama' => 'Cek Gula Darah', 'kategori' => 'laboratorium', 'tarif' => 35000],
            ['nama' => 'Injeksi / Suntik', 'kategori' => 'tindakan', 'tarif' => 20000],
            ['nama' => 'Perawatan Luka Ringan', 'kategori' => 'tindakan', 'tarif' => 30000],
            ['nama' => 'Pembersihan Karang Gigi', 'kategori' => 'gigi', 'tarif' => 75000],
        ];
        foreach ($layanan as $data) {
            Service::query()->updateOrCreate(['nama' => $data['nama']], $data + ['is_active' => true]);
        }

        $obat = [
            ['kode' => 'OBT-001', 'nama' => 'Paracetamol 500 mg', 'satuan' => 'tablet', 'harga' => 500, 'stok' => 240, 'stok_minimum' => 50],
            ['kode' => 'OBT-002', 'nama' => 'Amoxicillin 500 mg', 'satuan' => 'tablet', 'harga' => 1200, 'stok' => 120, 'stok_minimum' => 40],
            ['kode' => 'OBT-003', 'nama' => 'Antasida', 'satuan' => 'tablet', 'harga' => 600, 'stok' => 30, 'stok_minimum' => 40],
            ['kode' => 'OBT-004', 'nama' => 'Oralit', 'satuan' => 'sachet', 'harga' => 3000, 'stok' => 80, 'stok_minimum' => 20],
            ['kode' => 'OBT-005', 'nama' => 'Vitamin C 500 mg', 'satuan' => 'tablet', 'harga' => 800, 'stok' => 150, 'stok_minimum' => 30],
        ];
        foreach ($obat as $data) {
            Medicine::query()->updateOrCreate(['kode' => $data['kode']], $data + ['is_active' => true]);
        }

        $penyakit = [
            ['kode_icd10' => 'J06.9', 'nama' => 'Infeksi saluran pernapasan akut (ISPA)'],
            ['kode_icd10' => 'K30', 'nama' => 'Dispepsia'],
            ['kode_icd10' => 'R50.9', 'nama' => 'Demam, tidak spesifik'],
            ['kode_icd10' => 'K02.9', 'nama' => 'Karies gigi'],
            ['kode_icd10' => 'A09', 'nama' => 'Diare dan gastroenteritis'],
            ['kode_icd10' => 'I10', 'nama' => 'Hipertensi esensial (primer)'],
            ['kode_icd10' => 'E11', 'nama' => 'Diabetes melitus tipe 2'],
            ['kode_icd10' => 'M54.5', 'nama' => 'Nyeri punggung bawah'],
        ];
        foreach ($penyakit as $data) {
            Disease::query()->updateOrCreate(['kode_icd10' => $data['kode_icd10']], $data);
        }
    }
}
