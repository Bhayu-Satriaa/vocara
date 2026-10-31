<?php

namespace Database\Seeders;

use App\Models\ChatbotKnowledge;
use Illuminate\Database\Seeder;

/**
 * Isi awal basis pengetahuan chatbot (Tahap 1: FAQ tanpa AI).
 * Idempoten — aman dijalankan berkali-kali.
 */
class ChatbotKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $faq = [
            ['kategori' => 'Pendaftaran', 'pertanyaan' => 'syarat daftar berobat apa saja',
                'jawaban' => 'Cukup datang dengan kartu identitas (KTP/anak: kartu keluarga) dan nomor WhatsApp aktif. Pasien baru dibantu petugas membuat kartu QR di meja pendaftaran.'],
            ['kategori' => 'Pendaftaran', 'pertanyaan' => 'cara daftar pasien baru lewat online',
                'jawaban' => 'Masuk dengan akun Google, lengkapi data diri dan nomor WhatsApp, lalu tunggu persetujuan petugas. Setelah disetujui kamu punya nomor rekam medis dan kartu QR.'],
            ['kategori' => 'Pendaftaran', 'pertanyaan' => 'apakah harus bawa ktp saat berobat',
                'jawaban' => 'Ya, bawa KTP (atau kartu keluarga untuk anak) saat kunjungan pertama. Petugas mencocokkan identitas untuk memverifikasi kartu QR kamu.'],
            ['kategori' => 'Kartu QR', 'pertanyaan' => 'cara pakai kartu qr pasien',
                'jawaban' => 'Tunjukkan gambar QR di halaman Kartu QR kepada petugas. Petugas memindainya untuk check-in, membuka rekam medis, dan penyerahan obat.'],
            ['kategori' => 'Kartu QR', 'pertanyaan' => 'lupa bawa kartu qr atau hp mati',
                'jawaban' => 'Tidak masalah. Sebutkan nama dan tanggal lahirmu ke petugas — petugas bisa mencari data pasien secara manual.'],
            ['kategori' => 'Layanan', 'pertanyaan' => 'poli apa saja yang tersedia',
                'jawaban' => 'Saat ini tersedia Poli Umum, Poli Gigi, dan Poli Anak. Jadwal praktik tiap dokter bisa dilihat di halaman jadwal.'],
            ['kategori' => 'Layanan', 'pertanyaan' => 'berapa biaya konsultasi dokter',
                'jawaban' => 'Tarif konsultasi berbeda per dokter dan tercantum saat memilih jadwal. Total biaya dihitung petugas setelah pemeriksaan.'],
            ['kategori' => 'Layanan', 'pertanyaan' => 'jam buka layanan klinik',
                'jawaban' => 'Layanan pendaftaran dibuka pada hari kerja. Jam praktik tiap dokter mengikuti jadwal dokter yang tertera di halaman jadwal.'],
        ];

        foreach ($faq as $baris) {
            ChatbotKnowledge::query()->updateOrCreate(
                ['pertanyaan' => $baris['pertanyaan']],
                $baris + ['is_active' => true],
            );
        }

        $this->command?->info('Basis pengetahuan chatbot: '.count($faq).' FAQ siap.');
    }
}
