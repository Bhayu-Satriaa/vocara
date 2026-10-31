<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

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
    private const MAKS_RONDE = 3;

    /** Berapa pesan terakhir yang dikirim sebagai konteks. */
    private const KONTEKS_PESAN = 10;

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
            return null;
        }

        $pesan = [['role' => 'system', 'content' => $this->promptSistem($pasien)]];

        foreach ($percakapan->messages()->orderBy('id')->get()->slice(-self::KONTEKS_PESAN) as $baris) {
            $pesan[] = [
                'role' => $baris->pengirim === 'pengguna' ? 'user' : 'assistant',
                'content' => $baris->isi,
            ];
        }

        $intent = 'faq';

        for ($ronde = 0; $ronde < self::MAKS_RONDE; $ronde++) {
            $jawaban = $this->panggilModel($pesan);

            if ($jawaban === null) {
                return null;
            }

            $toolCalls = $jawaban['tool_calls'] ?? [];

            if ($toolCalls === []) {
                $isi = trim((string) ($jawaban['content'] ?? ''));

                return $isi === '' ? null : ['isi' => $isi, 'intent' => $intent];
            }

            $pesan[] = $jawaban;

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

        return null;
    }

    /** Panggil endpoint /chat/completions; kalau model utama gagal, coba model cadangan. */
    private function panggilModel(array $pesan): ?array
    {
        $model = [config('services.llm.model'), config('services.llm.model_fallback')];

        foreach (array_unique(array_filter($model)) as $nama) {
            $respon = Http::withToken(config('services.llm.key'))
                ->timeout((int) config('services.llm.timeout'))
                ->acceptJson()
                ->post(rtrim((string) config('services.llm.base_url'), '/').'/chat/completions', [
                    'model' => $nama,
                    'messages' => $pesan,
                    'tools' => $this->alat(),
                    'max_tokens' => (int) config('services.llm.max_tokens'),
                ]);

            if ($respon->successful()) {
                return $respon->json('choices.0.message');
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

        try {
            $visit = $this->booking->buat(
                $pasien,
                $dokter,
                $tanggal,
                (string) ($argumen['jam'] ?? ''),
                $argumen['keluhan'] ?? null,
                'chatbot',
            );
        } catch (ValidationException $e) {
            return ['ok' => false, 'pesan' => Arr::flatten($e->errors())[0] ?? 'Janji gagal dibuat.'];
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

    /** System prompt disimpan di server (bukan di browser) supaya tidak bisa diubah pasien. */
    private function promptSistem(?Patient $pasien): string
    {
        $hariIni = Carbon::today();

        $profil = $pasien
            ? "Data diri yang sedang dipakai: {$pasien->nama_lengkap} ({$pasien->no_rm}), status verifikasi: {$pasien->verification_status}."
            : 'Pasien belum punya data diri (belum disetujui admin).';

        return <<<PROMPT
        Kamu asisten klinik kesehatan bernama Rani. Jawab dalam Bahasa Indonesia, ramah dan singkat (maksimal 3 kalimat).

        Hari ini: {$hariIni->toDateString()} ({$hariIni->locale('id')->translatedFormat('l')}).
        {$profil}

        Aturan yang wajib dipatuhi:
        1. Kamu BUKAN pengganti dokter: jangan memberi diagnosis, dosis obat, atau janji kesembuhan. Untuk keluhan berat, arahkan ke petugas/dokter.
        2. Sebelum memanggil buat_janji, kamu WAJIB meringkas pilihan (dokter, poli, tanggal, hari, jam) dan meminta konfirmasi pasien. Panggil buat_janji HANYA setelah pasien menjawab setuju secara jelas.
        3. Kalau status verifikasi pasien bukan 'terverifikasi', jangan memanggil buat_janji. Jelaskan bahwa kartu QR harus ditunjukkan ke petugas dulu.
        4. Tanggal dan nama hari selalu ambil dari hasil tool, jangan menghitung sendiri.
        5. Pakai tool cek_jadwal sebelum menyebut jam yang tersedia.
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
                    'keluhan' => ['type' => 'string', 'description' => 'Keluhan awal pasien (opsional).'],
                ], 'required' => ['dokter_id', 'tanggal', 'jam']],
            ]],
            ['type' => 'function', 'function' => [
                'name' => 'cek_janji',
                'description' => 'Daftar janji temu aktif milik data diri yang sedang dipakai.',
                'parameters' => ['type' => 'object', 'properties' => [], 'required' => []],
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
