<?php

namespace App\Services;

use App\Models\ChatbotKnowledge;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Chatbot dasar (Tahap 1): menjawab dari basis pengetahuan tanpa memanggil model AI.
 * Logika pencocokan sengaja ditaruh di satu tempat supaya jalur web, dan nanti jalur
 * suara/chatbot dengan AI, memakai aturan yang sama.
 *
 * Rancangan: docs/SPEC-CHATBOT.md
 */
class ChatbotService
{
    /** Jumlah kata kunci minimal yang harus cocok agar jawaban dianggap relevan. */
    public const AMBANG = 2;

    /** Balasan ketika tidak ada pengetahuan yang cocok. */
    public const JAWABAN_TIDAK_DIKENAL = 'Maaf, saya belum punya jawaban untuk itu. Silakan hubungi petugas klinik, atau sampaikan langsung ke dokter saat kunjungan.';

    /** Kata yang terlalu umum, dibuang sebelum pencocokan. */
    private const KATA_UMUM = [
        'apa', 'apakah', 'bagaimana', 'cara', 'dan', 'atau', 'yang', 'di', 'ke', 'dari',
        'untuk', 'saya', 'aku', 'ini', 'itu', 'berapa', 'adalah', 'dengan', 'bisa',
        'tidak', 'ya', 'klinik', 'kah', 'nya', 'sih', 'dong',
    ];

    /** Pengetahuan aktif yang paling cocok, atau null kalau tidak ada yang cukup mirip. */
    public function cariJawaban(string $pertanyaan): ?ChatbotKnowledge
    {
        $kunciTanya = $this->kataKunci($pertanyaan);

        if ($kunciTanya === []) {
            return null;
        }

        $terbaik = null;
        $skorTerbaik = 0;

        /** @var Collection<int, ChatbotKnowledge> $pengetahuan */
        $pengetahuan = ChatbotKnowledge::query()->where('is_active', true)->get();

        foreach ($pengetahuan as $baris) {
            $skor = count(array_intersect(
                $kunciTanya,
                $this->kataKunci($baris->pertanyaan.' '.$baris->kategori),
            ));

            if ($skor > $skorTerbaik) {
                $skorTerbaik = $skor;
                $terbaik = $baris;
            }
        }

        return $skorTerbaik >= self::AMBANG ? $terbaik : null;
    }

    /** Kata kunci bersih: huruf kecil, tanpa tanda baca, tanpa kata umum. */
    private function kataKunci(string $teks): array
    {
        $bersih = preg_replace('/[^a-z0-9 ]/', ' ', Str::lower($teks)) ?? '';
        $kata = preg_split('/\s+/', trim($bersih)) ?: [];

        return array_values(array_diff(array_unique(array_filter($kata)), self::KATA_UMUM));
    }
}
