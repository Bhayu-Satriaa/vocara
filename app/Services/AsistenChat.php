<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatbotKnowledge;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Visit;
use Carbon\Carbon;
use Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\StreamInterface;

/**
 * Otak chatbot: memanggil model dari gateway OpenAI-compatible (9Router lokal) dan
 * menjalankan tool yang dipilih model.
 *
 * ATURAN yang dipegang di kode, bukan cuma di prompt:
 * - Pembuatan janji DITOLAK kalau pasien belum `terverifikasi` petugas — walaupun model
 *   memanggil tool-nya. Prompt hanya lapis pertama, kode ini lapis kedua.
 * - Tanggal dan nama hari selalu dikembalikan backend di setiap hasil tool, supaya model
 *   tidak menghitung hari sendiri.
 *
 * Rancangan: docs/SPEC-BOOKING-CHATBOT.md
 */
class AsistenChat
{
    /** Maksimal putaran tool calling supaya percakapan tidak menggantung. */
    private const MAKS_RONDE = 4;

    /** Berapa pesan terakhir yang dikirim sebagai konteks. */
    private const KONTEKS_PESAN = 10;

    /** Berapa lama slot yang sudah ditawarkan masih dianggap berlaku. */
    private const MENIT_TAWARAN = 30;

    /** Batas jumlah FAQ yang dikirim ke model, supaya prompt tidak membengkak. */
    private const MAKS_FAQ = 30;

    /**
     * Kunci cache tempat slot yang sudah ditawarkan ke pasien disimpan, per percakapan.
     * Dipakai supaya buat_janji tidak bisa memakai dokter/jam di luar yang ditawarkan.
     */
    private ?string $kunciTawaran = null;

    public function __construct(private readonly BookingService $booking) {}

    /** Ada gateway model yang bisa dipakai? Kalau tidak, chatbot jalan mode FAQ saja. */
    public function tersedia(): bool
    {
        return filled(config('services.llm.base_url')) && filled(config('services.llm.model'));
    }

    /**
     * Balas satu pertanyaan pasien.
     *
     * @return array{isi: string, intent: string}|null null kalau gateway bermasalah
     *         (pemanggil sebaiknya jatuh ke jawaban FAQ biasa)
     */
    public function balas(ChatConversation $percakapan, ?Patient $pasien): ?array
    {
        if (! $this->tersedia()) {
            Log::warning('Chatbot tanpa gateway model: LLM_BASE_URL/LLM_MODEL belum diisi');

            return null;
        }

        $this->kunciTawaran = 'chat.tawaran.'.$percakapan->id;

        $pesan = $this->siapkanPesan($percakapan, $pasien);

        // Bawaan 'ai': jawaban dari model tanpa tool. Kalau ada tool yang dipanggil,
        // nilai ini ditimpa intent tool-nya ('tanya_jadwal', 'buat_janji', ...).
        $intent = 'ai';

        for ($ronde = 0; $ronde < self::MAKS_RONDE; $ronde++) {
            // Putaran terakhir: tool dilarang supaya model WAJIB menjawab dengan teks.
            // Tanpa ini, percakapan yang ramai memakai tool berhenti tanpa jawaban sama sekali
            // dan pasien hanya menerima pesan cadangan ("asisten sedang sibuk").
            $jawaban = $this->panggilModel($pesan, $ronde === self::MAKS_RONDE - 1);

            if ($jawaban === null) {
                return null;
            }

            $toolCalls = $jawaban['tool_calls'] ?? [];

            if ($toolCalls === []) {
                $isi = $this->bersihkanJawaban((string) ($jawaban['content'] ?? ''));

                if ($isi === '') {
                    Log::warning('Jawaban model kosong setelah dibersihkan', ['model' => config('services.llm.model')]);

                    return null;
                }

                return ['isi' => $isi, 'intent' => $intent];
            }

            // Kirim balik HANYA field yang dikenal API (jangan ikutkan `reasoning_content`):
            // sebagian gateway menolak pesan assistant dengan field tambahan.
            $pesan[] = array_filter([
                'role' => 'assistant',
                'content' => $jawaban['content'] ?? null,
                'tool_calls' => $toolCalls,
            ], fn ($nilai) => $nilai !== null);

            foreach ($toolCalls as $panggilan) {
                $nama = $panggilan['function']['name'] ?? '';
                $argumen = json_decode((string) ($panggilan['function']['arguments'] ?? '{}'), true) ?: [];

                $intent = $this->intentUntuk($nama);
                $hasil = $this->jalankanTool($nama, $argumen, $pasien);

                $pesan[] = [
                    'role' => 'tool',
                    'tool_call_id' => $panggilan['id'] ?? '',
                    'content' => json_encode($hasil, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        Log::warning('Model tidak menjawab setelah batas putaran tool', ['maks_ronde' => self::MAKS_RONDE]);

        return null;
    }

    /**
     * Versi streaming dari `balas()`: hasilkan potongan jawaban begitu tiba.
     *
     * Kejadian yang dihasilkan:
     * - `['delta' => 'teks']`   — potongan jawaban untuk langsung ditampilkan
     * - `['alat' => 'cek_jadwal']` — model sedang memakai tool (bisa ditampilkan sebagai status)
     * - `['selesai' => true, 'isi' => '...', 'intent' => '...']`
     * - `['gagal' => true]` — gateway bermasalah, pemanggil sebaiknya jatuh ke FAQ
     *
     * @return Generator<int, array<string, mixed>>
     */
    public function alirBalas(ChatConversation $percakapan, ?Patient $pasien): Generator
    {
        if (! $this->tersedia()) {
            yield ['gagal' => true];

            return;
        }

        $this->kunciTawaran = 'chat.tawaran.'.$percakapan->id;
        $pesan = $this->siapkanPesan($percakapan, $pasien);
        $intent = 'ai';
        $teksRondeTerakhir = '';

        for ($ronde = 0; $ronde < self::MAKS_RONDE; $ronde++) {
            $jawaban = null;

            foreach ($this->panggilModelAlir($pesan, $jawaban, $ronde === self::MAKS_RONDE - 1) as $kejadian) {
                $teksRondeTerakhir .= $kejadian['delta'];

                yield $kejadian;
            }

            if ($jawaban === null) {
                yield ['gagal' => true];

                return;
            }

            $toolCalls = $jawaban['tool_calls'] ?? [];

            if ($toolCalls === []) {
                yield ['selesai' => true, 'isi' => $this->bersihkanJawaban($teksRondeTerakhir), 'intent' => $intent];

                return;
            }

            $pesan[] = array_filter([
                'role' => 'assistant',
                'content' => $jawaban['content'] ?? null,
                'tool_calls' => $toolCalls,
            ], fn ($nilai) => $nilai !== null);

            foreach ($toolCalls as $panggilan) {
                $nama = $panggilan['function']['name'] ?? '';
                $argumen = json_decode((string) ($panggilan['function']['arguments'] ?? '{}'), true) ?: [];

                $intent = $this->intentUntuk($nama);
                $hasil = $this->jalankanTool($nama, $argumen, $pasien);

                yield ['alat' => $nama];

                $pesan[] = [
                    'role' => 'tool',
                    'tool_call_id' => $panggilan['id'] ?? '',
                    'content' => json_encode($hasil, JSON_UNESCAPED_UNICODE),
                ];
            }

            // Teks ronde tool tidak dihitung sebagai jawaban akhir.
            $teksRondeTerakhir = '';
        }

        yield ['selesai' => true, 'isi' => $this->bersihkanJawaban($teksRondeTerakhir), 'intent' => $intent];
    }

    /** Ringkas keluhan pasien jadi maksimal 120 karakter; null kalau kosong. */
    private function ringkasKeluhan(?string $keluhan): ?string
    {
        $teks = trim((string) $keluhan);

        return $teks === '' ? null : mb_substr($teks, 0, 120, 'UTF-8');
    }

    /**
     * Buang sisa token asing yang kadang bocor dari model sebelum jawaban disimpan.
     *
     * Contoh nyata (5 Nov 2026): "Baik, saya bantu buatkan janji temu.的话语Pertama, …"
     * — aksara Mandarin ikut terkirim. Aksara non-Latin yang tidak mungkin dipakai
     * jawaban Bahasa Indonesia dibuang, lalu spasi dirapikan.
     */
    private function bersihkanJawaban(string $teks): string
    {
        $asing = '/[\x{2E80}-\x{2EFF}\x{3000}-\x{303F}\x{3040}-\x{30FF}'
            .'\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}'
            .'\x{AC00}-\x{D7AF}\x{0400}-\x{04FF}\x{0600}-\x{06FF}'
            .'\x{0900}-\x{097F}\x{0E00}-\x{0E7F}]+/u';

        $bersih = preg_replace($asing, '', $teks) ?? $teks;

        // Kata yang sering salah tulis model -> istilah baku klinik ("poly" -> "poli").
        $bersih = preg_replace_callback(
            '/\bpoly\b/ui',
            static fn (array $c): string => mb_substr($c[0], 0, 1) === mb_strtoupper(mb_substr($c[0], 0, 1)) ? 'Poli' : 'poli',
            $bersih
        ) ?? $bersih;

        // Tanda pisah tipografis (— / –) tidak dipakai lagi:
        // antar angka jadi tanda hubung biasa (08.00-12.00), pemisah kalimat jadi koma.
        $bersih = preg_replace('/(\d)\s*[—–]\s*(\d)/u', '$1-$2', $bersih) ?? $bersih;
        $bersih = preg_replace('/\s*[—–]\s*/u', ', ', $bersih) ?? $bersih;
        $bersih = str_replace([', ,', ',,', ' ,'], [',', ',', ','], $bersih);
        $bersih = preg_replace('/,\s*([.,!?;:])/u', '$1', $bersih) ?? $bersih;
        $bersih = preg_replace('/^[\s,]+/u', '', $bersih) ?? $bersih;

        // Kata Indonesia yang sering menempel ke angka ("Jumat6 November", "jam08:00")
        // dipisah supaya mudah dibaca. Angka tidak pernah menempel ke kata dalam jawaban ini,
        // jadi aman; tanda hubung seperti RM-000001 tidak ikut diubah.
        $bersih = preg_replace(
            '/\b(hari|senin|selasa|rabu|kamis|jumat|jum\'at|sabtu|minggu|januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember|jam|pukul|tanggal|tahun|pukul)(\d)/iu',
            '$1 $2',
            $bersih
        ) ?? $bersih;

        // Rapikan sisa spasi ganda.
        $bersih = preg_replace('/[ \t]{2,}/', ' ', $bersih) ?? $bersih;

        return trim($bersih);
    }

    /** Susun daftar pesan (system + riwayat) yang dikirim ke model. */
    private function siapkanPesan(ChatConversation $percakapan, ?Patient $pasien): array
    {
        $pesan = [['role' => 'system', 'content' => $this->promptSistem($pasien)]];

        foreach ($percakapan->messages()->orderBy('id')->get()->slice(-self::KONTEKS_PESAN) as $baris) {
            $pesan[] = [
                'role' => $baris->pengirim === 'pengguna' ? 'user' : 'assistant',
                'content' => $baris->isi,
            ];
        }

        return $pesan;
    }

    /**
     * Panggil model dengan `stream: true` dan hasilkan potongan teks.
     *
     * Pesan utuh (teks + tool_calls yang sudah dirangkai dari delta) dikembalikan lewat
     * parameter `$hasil`, supaya alurnya sama dengan mode non-streaming.
     *
     * @param  array<int, array<string, mixed>>  $pesan
     * @param  array<string, mixed>|null  $hasil
     * @return Generator<int, array{delta: string}>
     */
    private function panggilModelAlir(array $pesan, ?array &$hasil, bool $wajibTeks = false): Generator
    {
        $hasil = null;
        $model = array_unique(array_filter([config('services.llm.model'), config('services.llm.model_fallback')]));

        foreach ($model as $nama) {
            try {
                $respon = Http::withToken(config('services.llm.key'))
                    ->timeout((int) config('services.llm.timeout'))
                    ->withOptions(['stream' => true])
                    ->acceptJson()
                    ->post(rtrim((string) config('services.llm.base_url'), '/').'/chat/completions', array_filter([
                        'model' => $nama,
                        'messages' => $pesan,
                        'tools' => $wajibTeks ? null : $this->alat(),
                        'max_tokens' => (int) config('services.llm.max_tokens'),
                        'stream' => true,
                        'reasoning_effort' => config('services.llm.reasoning_effort'),
                    ]));
            } catch (\Throwable $e) {
                Log::warning('Gateway model tidak bisa dihubungi (mode streaming)', [
                    'model' => $nama,
                    'pesan' => $e->getMessage(),
                ]);

                continue;
            }

            if (! $respon->successful()) {
                Log::warning('Gateway model menolak permintaan (mode streaming)', [
                    'model' => $nama,
                    'status' => $respon->status(),
                    'body' => substr((string) $respon->body(), 0, 300),
                ]);

                continue;
            }

            $isi = '';
            $toolCalls = [];
            $badan = $respon->toPsrResponse()->getBody();

            foreach ($this->uraiSse($badan) as $data) {
                $delta = $data['choices'][0]['delta'] ?? [];

                if (($delta['content'] ?? '') !== '') {
                    $isi .= $delta['content'];

                    yield ['delta' => $delta['content']];
                }

                // tool_calls tiba bertahap (per indeks): gabungkan potongan argumennya.
                foreach ($delta['tool_calls'] ?? [] as $panggilan) {
                    $indeks = (int) ($panggilan['index'] ?? 0);

                    $toolCalls[$indeks]['id'] = $panggilan['id'] ?? ($toolCalls[$indeks]['id'] ?? 'call_'.$indeks);
                    $toolCalls[$indeks]['type'] = 'function';
                    $toolCalls[$indeks]['function']['name'] = $panggilan['function']['name'] ?? ($toolCalls[$indeks]['function']['name'] ?? '');
                    $toolCalls[$indeks]['function']['arguments'] = ($toolCalls[$indeks]['function']['arguments'] ?? '').($panggilan['function']['arguments'] ?? '');
                }
            }

            $hasil = ['content' => $isi, 'tool_calls' => array_values($toolCalls)];

            return;
        }
    }

    /**
     * Baca badan balasan berformat SSE dan hasilkan tiap muatan JSON-nya.
     *
     * PSR-7 tidak menyediakan `readLine()`, jadi barisnya dipecah sendiri supaya
     * tetap bekerja di atas stream apa pun (termasuk balasan palsu di test).
     *
     * @return Generator<int, array<string, mixed>>
     */
    private function uraiSse(StreamInterface $badan): Generator
    {
        $sisa = '';

        while (! $badan->eof()) {
            $sisa .= $badan->read(8192);

            while (($pos = strpos($sisa, "\n")) !== false) {
                $baris = trim(substr($sisa, 0, $pos));
                $sisa = substr($sisa, $pos + 1);

                if (! str_starts_with($baris, 'data:')) {
                    continue;
                }

                $muatan = trim(substr($baris, 5));

                if ($muatan === '' || $muatan === '[DONE]') {
                    continue;
                }

                $data = json_decode($muatan, true);

                if (is_array($data)) {
                    yield $data;
                }
            }
        }
    }

    /** Panggil endpoint /chat/completions; kalau model utama gagal, coba model cadangan. */
    private function panggilModel(array $pesan, bool $wajibTeks = false): ?array
    {
        $model = [config('services.llm.model'), config('services.llm.model_fallback')];
        $anggaran = (int) config('services.llm.max_tokens');

        foreach (array_unique(array_filter($model)) as $nama) {
            // Model penalar bisa menghabiskan anggaran token untuk "berpikir" sehingga
            // `content` keluar kosong (finish_reason=length). Coba ulang dengan anggaran
            // dua kali lipat sebelum menyerah — kalau tidak, jawaban hilang padahal gateway sehat.
            for ($percobaan = 1; $percobaan <= 2; $percobaan++) {
                try {
                    $respon = Http::withToken(config('services.llm.key'))
                        ->timeout((int) config('services.llm.timeout'))
                        ->acceptJson()
                        ->post(rtrim((string) config('services.llm.base_url'), '/').'/chat/completions', array_filter([
                            'model' => $nama,
                            'messages' => $pesan,
                            // Putaran terakhir: parameter `tools` dibuang sama sekali (bukan cuma
                            // tool_choice=none, karena sebagian model mengabaikannya) supaya model
                            // WAJIB menjawab teks dan percakapan tidak berhenti tanpa jawaban.
                            'tools' => $wajibTeks ? null : $this->alat(),
                            'max_tokens' => $anggaran,
                            'reasoning_effort' => config('services.llm.reasoning_effort'),
                        ]));
                } catch (\Throwable $e) {
                    // Gateway tidak bisa dihubungi (koneksi ditolak / waktu habis): dicatat
                    // supaya tidak lagi gagal diam-diam, lalu coba model berikutnya.
                    Log::warning('Gateway model tidak bisa dihubungi', [
                        'model' => $nama,
                        'pesan' => $e->getMessage(),
                    ]);

                    break;
                }

                if (! $respon->successful()) {
                    // Dicatat supaya kegagalan gateway bisa ditelusuri (tanpa menulis kunci).
                    Log::warning('Gateway model menolak permintaan', [
                        'model' => $nama,
                        'status' => $respon->status(),
                        'body' => substr((string) $respon->body(), 0, 300),
                    ]);

                    break;
                }

                $jawaban = $respon->json('choices.0.message');
                $alasan = $respon->json('choices.0.finish_reason');

                // Bersihkan lebih dulu: jawaban yang isinya hanya aksara asing (mis. bocoran
                // Mandarin dari model) akan kosong setelah dibersihkan, jadi tidak layak kirim.
                if (is_array($jawaban) && isset($jawaban['content'])) {
                    $jawaban['content'] = $this->bersihkanJawaban((string) $jawaban['content']);
                }

                $kosong = trim((string) ($jawaban['content'] ?? '')) === ''
                    && ($jawaban['tool_calls'] ?? []) === [];

                if (! $kosong && $alasan !== 'length') {
                    return $jawaban;
                }

                Log::warning('Jawaban model kosong atau terpotong', [
                    'model' => $nama,
                    'finish_reason' => $alasan,
                    'max_tokens' => $anggaran,
                    'percobaan' => $percobaan,
                ]);

                if ($percobaan === 2) {
                    // Teks kosong tapi ada tool_calls: masih berguna, pakai apa adanya.
                    // Kalau benar-benar kosong, JANGAN berhenti — coba model cadangan.
                    if (! $kosong) {
                        return $jawaban;
                    }

                    break;
                }

                $anggaran = min($anggaran * 2, 3000);
            }
        }

        return null;
    }

    /** Jalankan tool yang diminta model. Semua hasil memuat tanggal + nama hari. */
    private function jalankanTool(string $nama, array $argumen, ?Patient $pasien): array
    {
        $tanggal = $this->tanggalSah($argumen['tanggal'] ?? null);

        return match ($nama) {
            'daftar_dokter' => $this->daftarDokter($tanggal),
            'cek_jadwal' => $this->cekJadwal((int) ($argumen['dokter_id'] ?? 0), $tanggal),
            'buat_janji' => $this->buatJanji($pasien, $argumen, $tanggal),
            'cek_janji' => $this->cekJanji($pasien),
            'batal_janji' => $this->batalJanji($pasien, (int) ($argumen['visit_id'] ?? 0)),
            default => ['ok' => false, 'pesan' => "Tool {$nama} tidak dikenal."],
        };
    }

    private function daftarDokter(string $tanggal): array
    {
        $dokter = $this->booking->dokterPraktikPada($tanggal)->map(fn (Doctor $d) => [
            'dokter_id' => $d->id,
            'nama' => $d->user?->name,
            'poli' => $d->poli?->nama,
            'spesialisasi' => $d->spesialisasi,
        ])->all();

        return $dokter === []
            ? ['ok' => false, 'tanggal' => $tanggal, 'hari' => $this->booking->namaHari($tanggal), 'pesan' => 'Tidak ada dokter yang praktik pada tanggal itu.']
            : ['ok' => true, 'tanggal' => $tanggal, 'hari' => $this->booking->namaHari($tanggal), 'dokter' => $dokter];
    }

    private function cekJadwal(int $dokterId, string $tanggal): array
    {
        $dokter = Doctor::query()->with('user', 'poli')->find($dokterId);

        if (! $dokter) {
            return ['ok' => false, 'pesan' => 'Dokter tidak ditemukan.'];
        }

        $slot = $this->booking->slotTersedia($dokter, $tanggal);

        // Catat slot yang baru ditawarkan ke pasien. buat_janji hanya boleh memakai slot
        // ini, supaya pasien tidak menyetujui dokter/jam A lalu mendapat B.
        if ($slot !== [] && $this->kunciTawaran !== null) {
            Cache::put($this->kunciTawaran, [
                'dokter_id' => $dokter->id,
                'tanggal' => $tanggal,
                'jam' => array_map(fn ($jam) => substr((string) $jam, 0, 5), $slot),
            ], now()->addMinutes(self::MENIT_TAWARAN));
        }

        return [
            'ok' => true,
            'dokter' => $dokter->user?->name,
            'poli' => $dokter->poli?->nama,
            'tanggal' => $tanggal,
            'hari' => $this->booking->namaHari($tanggal),
            'jam_tersedia' => $slot,
            'pesan' => $slot === [] ? 'Tidak ada jam kosong pada tanggal itu.' : null,
        ];
    }

    /** Gerbang verifikasi ada di sini — bukan hanya di prompt. */
    private function buatJanji(?Patient $pasien, array $argumen, string $tanggal): array
    {
        if (! $pasien) {
            return ['ok' => false, 'pesan' => 'Pasien belum punya data diri. Minta pasien melengkapi profil dan menunggu persetujuan admin.'];
        }

        if (! $pasien->sudahTerverifikasi()) {
            return [
                'ok' => false,
                'pesan' => 'Kartu QR pasien belum diverifikasi petugas. Minta pasien menunjukkan kartu QR di meja pendaftaran dulu; janji belum dibuat.',
                'status_verifikasi' => $pasien->verification_status,
            ];
        }

        $dokter = Doctor::query()->find((int) ($argumen['dokter_id'] ?? 0));

        if (! $dokter) {
            return ['ok' => false, 'pesan' => 'Dokter tidak ditemukan — sebutkan dokter yang dipilih pasien.'];
        }

        // Paku slot: janji hanya boleh dibuat untuk dokter/tanggal/jam yang benar-benar
        // sudah ditawarkan (lewat cek_jadwal) dan dikonfirmasi pasien.
        $tawaran = $this->kunciTawaran !== null ? Cache::get($this->kunciTawaran) : null;
        $jam = substr(trim((string) ($argumen['jam'] ?? '')), 0, 5);

        if (! is_array($tawaran)) {
            return ['ok' => false, 'pesan' => 'Belum ada slot yang ditawarkan di percakapan ini. Panggil cek_jadwal dulu, tawarkan jam kosongnya ke pasien, tunggu konfirmasi, baru panggil buat_janji.'];
        }

        if ((int) $tawaran['dokter_id'] !== (int) $dokter->id || $tawaran['tanggal'] !== $tanggal) {
            return [
                'ok' => false,
                'pesan' => 'Dokter atau tanggal berbeda dari yang ditawarkan sebelumnya. Tawarkan pilihan baru ke pasien dan tunggu konfirmasi lagi.',
                'tawaran_sebelumnya' => $tawaran,
            ];
        }

        if (! in_array($jam, $tawaran['jam'], true)) {
            return [
                'ok' => false,
                'pesan' => "Jam {$jam} tidak termasuk jam yang ditawarkan. Tawarkan hanya jam pada tawaran_sebelumnya.",
                'tawaran_sebelumnya' => $tawaran,
            ];
        }

        try {
            $visit = $this->booking->buat(
                $pasien,
                $dokter,
                $tanggal,
                (string) ($argumen['jam'] ?? ''),
                $this->ringkasKeluhan($argumen['keluhan'] ?? null),
                'chatbot',
            );
        } catch (ValidationException $e) {
            return ['ok' => false, 'pesan' => Arr::flatten($e->errors())[0] ?? 'Janji gagal dibuat.'];
        }

        // Tawaran sudah terpakai — buang supaya tidak bisa dipakai dua kali.
        if ($this->kunciTawaran !== null) {
            Cache::forget($this->kunciTawaran);
        }

        return [
            'ok' => true,
            'visit_id' => $visit->id,
            'nomor_rekam_medis' => $pasien->no_rm,
            'dokter' => $dokter->user?->name,
            'tanggal' => $tanggal,
            'hari' => $this->booking->namaHari($tanggal),
            'jam' => substr((string) $visit->jam, 0, 5),
            'pesan' => 'Janji berhasil disimpan. Ingatkan pasien datang 10 menit lebih awal.',
        ];
    }

    private function cekJanji(?Patient $pasien): array
    {
        if (! $pasien) {
            return ['ok' => false, 'pesan' => 'Belum ada data diri untuk dicari.'];
        }

        $janji = $this->booking->janjiMilik($pasien)->map(fn (Visit $v) => [
            'visit_id' => $v->id,
            'dokter' => $v->doctor?->user?->name,
            'poli' => $v->poli?->nama,
            'tanggal' => $v->tgl_kunjungan?->toDateString(),
            'hari' => $this->booking->namaHari($v->tgl_kunjungan?->toDateString() ?? ''),
            'jam' => substr((string) $v->jam, 0, 5),
            'status' => $v->status,
        ])->all();

        return ['ok' => true, 'janji' => $janji, 'pesan' => $janji === [] ? 'Belum ada janji aktif.' : null];
    }

    private function batalJanji(?Patient $pasien, int $visitId): array
    {
        if (! $pasien) {
            return ['ok' => false, 'pesan' => 'Belum ada data diri.'];
        }

        $visit = Visit::query()->whereKey($visitId)->where('patient_id', $pasien->id)->first();

        if (! $visit) {
            return ['ok' => false, 'pesan' => 'Janji itu bukan milik data diri yang sedang dipakai.'];
        }

        $this->booking->batal($visit);

        return ['ok' => true, 'visit_id' => $visit->id, 'pesan' => 'Janji dibatalkan dan jamnya sudah bebas lagi.'];
    }

    private function intentUntuk(string $tool): string
    {
        return match ($tool) {
            'daftar_dokter', 'cek_jadwal' => 'tanya_jadwal',
            'buat_janji' => 'buat_janji',
            'cek_janji' => 'cek_janji',
            'batal_janji' => 'batal_janji',
            default => 'faq',
        };
    }

    /** Tanggal dari model selalu divalidasi; bawaan besok kalau kosong/ngawur. */
    private function tanggalSah(?string $tanggal): string
    {
        try {
            return Carbon::parse($tanggal ?: 'tomorrow')->toDateString();
        } catch (\Throwable) {
            return Carbon::tomorrow()->toDateString();
        }
    }

    /**
     * Semua FAQ aktif dari basis pengetahuan yang dikelola admin
     * (`/dashboard/pengetahuan-chatbot`). Dikirim apa adanya ke model supaya pertanyaan
     * layanan (jam buka, biaya, syarat, kartu QR) dijawab dari data klinik — bukan dari
     * karangan model, dan bukan hanya saat gateway mati.
     */
    private function basisPengetahuan(): string
    {
        $entri = ChatbotKnowledge::query()
            ->where('is_active', true)
            ->orderBy('kategori')
            ->orderBy('id')
            ->limit(self::MAKS_FAQ)
            ->get()
            ->map(fn (ChatbotKnowledge $faq) => "- [{$faq->kategori}] {$faq->pertanyaan}: {$faq->jawaban}")
            ->all();

        if ($entri === []) {
            return '';
        }

        return "Basis pengetahuan klinik (pakai kalau cocok; jangan mengarang di luar ini dan di luar hasil tool):\n"
            .implode("\n", $entri)
            ."\nUntuk jam, tanggal, nama dokter, dan ketersediaan slot: tetap WAJIB dari hasil tool.\n"
            ."Kalau jawabannya tidak ada di daftar ini maupun di hasil tool: katakan belum tahu dan arahkan ke petugas.\n";
    }

    /** System prompt disimpan di server (bukan di browser) supaya tidak bisa diubah pasien. */
    private function promptSistem(?Patient $pasien): string
    {
        $hariIni = Carbon::today();

        $profil = $pasien
            ? "Data diri yang sedang dipakai: {$pasien->nama_lengkap} ({$pasien->no_rm}), status verifikasi: {$pasien->verification_status}."
            : 'Pasien belum punya data diri (belum disetujui admin).';

        $faq = $this->basisPengetahuan();

        return <<<PROMPT
        Kamu asisten klinik kesehatan bernama Rani. Jawab dalam Bahasa Indonesia, ramah dan singkat: maksimal 2 kalimat pendek, tanpa daftar/bullet/markdown kecuali pasien memintanya.

        Hari ini: {$hariIni->toDateString()} ({$hariIni->locale('id')->translatedFormat('l')}).
        {$profil}
        {$faq}
        Aturan yang wajib dipatuhi:
        1. Kamu BUKAN pengganti dokter: jangan memberi diagnosis, dosis obat, atau janji kesembuhan. Untuk keluhan berat, arahkan ke petugas/dokter.
        2. Sebelum memanggil buat_janji, kamu WAJIB meringkas pilihan (dokter, poli, tanggal, hari, jam) dan meminta konfirmasi pasien. Panggil buat_janji HANYA setelah pasien menjawab setuju secara jelas.
        3. Kalau status verifikasi pasien bukan 'terverifikasi', jangan memanggil buat_janji. Jelaskan bahwa kartu QR harus ditunjukkan ke petugas dulu.
        4. Tanggal, nama hari, jam, dan nama dokter WAJIB disalin persis dari hasil tool. Jangan mengarang, menebak tahun, atau menambah hari/jam yang tidak ada di hasil tool.
        5. Pakai tool cek_jadwal sebelum menyebut jam yang tersedia. Pertanyaan soal dokter, poli, hari praktik, atau slot WAJIB dijawab dari hasil tool daftar_dokter/cek_jadwal — jangan dari ingatanmu.
        6. Kalau jawaban tidak ada di hasil tool dan tidak ada di pengetahuan klinik, katakan belum tahu dan arahkan ke petugas. Jangan mengarang biaya, jam buka, atau nomor telepon.
        7. Tulis jawaban HANYA dengan huruf Latin Bahasa Indonesia. Jangan pernah menulis aksara Mandarin/Arab/Kiril, dan jangan mencampur kata Inggris (kecuali istilah lazim: KTP, QR, WhatsApp, check-in).
        8. JANGAN pakai tanda pisah panjang "—" atau "–" sama sekali. Pakai koma atau titik untuk memisah kalimat, dan tanda hubung biasa hanya untuk rentang angka.
        9. Tulis "poli", bukan "poly". Contoh: poli umum, poli gigi, poli anak.
        10. Keluhan awal ditanya PALING AKHIR dan hanya sekali. Jangan menanyakan keluhan sebelum dokter, poli, tanggal, dan jam sudah jelas. Setelah pasien setuju pada ringkasan pilihan, baru tanyakan sekali: "Ada keluhan yang mau disampaikan ke dokter? (boleh dilewati)". Kalau pasien sudah menyebut keluhannya sendiri, JANGAN bertanya lagi, cukup ringkas jadi maksimal 120 karakter. Setelah pasien menjawab keluhan itu (menyebut keluhan atau minta dilewati), LANGSUNG panggil buat_janji TANPA meminta konfirmasi ulang — konfirmasi sudah didapat sebelumnya. Kalau pasien minta dilewati, panggil buat_janji tanpa parameter keluhan.
        PROMPT;
    }

    /** Deklarasi tool dalam format OpenAI. */
    private function alat(): array
    {
        return [
            ['type' => 'function', 'function' => [
                'name' => 'daftar_dokter',
                'description' => 'Daftar dokter yang praktik pada tanggal tertentu beserta polinya.',
                'parameters' => ['type' => 'object', 'properties' => [
                    'tanggal' => ['type' => 'string', 'description' => 'Tanggal YYYY-MM-DD. Kosongkan untuk besok.'],
                ], 'required' => []],
            ]],
            ['type' => 'function', 'function' => [
                'name' => 'cek_jadwal',
                'description' => 'Jam praktik yang masih kosong untuk satu dokter pada satu tanggal.',
                'parameters' => ['type' => 'object', 'properties' => [
                    'dokter_id' => ['type' => 'integer', 'description' => 'ID dokter dari daftar_dokter.'],
                    'tanggal' => ['type' => 'string', 'description' => 'Tanggal YYYY-MM-DD.'],
                ], 'required' => ['dokter_id', 'tanggal']],
            ]],
            ['type' => 'function', 'function' => [
                'name' => 'buat_janji',
                'description' => 'Simpan janji temu. Panggil hanya setelah pasien menyetujui ringkasan pilihan.',
                'parameters' => ['type' => 'object', 'properties' => [
                    'dokter_id' => ['type' => 'integer'],
                    'tanggal' => ['type' => 'string', 'description' => 'Tanggal YYYY-MM-DD.'],
                    'jam' => ['type' => 'string', 'description' => 'Jam HH:MM dari cek_jadwal.'],
                    'keluhan' => ['type' => 'string', 'description' => 'Ringkasan keluhan awal pasien, maksimal 120 karakter (opsional). Isi HANYA dari yang sudah disampaikan pasien; kosongkan kalau pasien menolak atau belum menyebutkannya.'],
                ], 'required' => ['dokter_id', 'tanggal', 'jam']],
            ]],
            ['type' => 'function', 'function' => [
                'name' => 'cek_janji',
                'description' => 'Daftar janji temu aktif milik data diri yang sedang dipakai.',
                // `properties` harus dikirim sebagai objek JSON ({}), bukan array ([]) —
                // kalau array, gateway menolak: "parameters.properties must be an object".
                'parameters' => ['type' => 'object', 'properties' => (object) []],
            ]],
            ['type' => 'function', 'function' => [
                'name' => 'batal_janji',
                'description' => 'Batalkan satu janji temu milik pasien.',
                'parameters' => ['type' => 'object', 'properties' => [
                    'visit_id' => ['type' => 'integer', 'description' => 'ID janji dari cek_janji.'],
                ], 'required' => ['visit_id']],
            ]],
        ];
    }
}
