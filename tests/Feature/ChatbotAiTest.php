<?php

namespace Tests\Feature;

use App\Models\ChatbotKnowledge;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Poli;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Chatbot dengan otak model (tool calling) — Fase 3 docs/SPEC-BOOKING-CHATBOT.md.
 *
 * Gateway model dipalsukan lewat Http::fake supaya test tidak butuh 9Router hidup.
 *
 * PENTING: `Http::fake()` MENGGABUNG stub, bukan menimpa — panggilan kedua menambah
 * stub baru di belakang yang lama. Kalau satu test mendaftarkan fake dua kali (mis. satu
 * per putaran percakapan), stub LAMA yang menang dan test jadi menguji hal yang salah.
 * Karena itu satu test di sini hanya mendaftarkan SATU fake: helper `fakeSkrip()` memilih
 * tool berdasarkan kata pemicu pada pesan pengguna terakhir, bukan urutan panggilan.
 */
class ChatbotAiTest extends TestCase
{
    use RefreshDatabase;

    private function pasien(bool $terverifikasi = true, string $email = 'pasien@klinik.test'): User
    {
        /** @var User $user */
        $user = User::factory()->pasien()->create(['email' => $email]);

        Patient::query()->create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Budi Santoso',
            'no_wa' => '081234567890',
            'tgl_lahir' => '1995-05-20',
            'jenis_kelamin' => 'L',
            'verification_status' => $terverifikasi ? 'terverifikasi' : 'belum',
        ]);

        return $user->refresh();
    }

    private function dokter(string $nama = 'drg. Beni Saputra', string $namaPoli = 'Poli Gigi'): Doctor
    {
        $poli = Poli::query()->firstOrCreate(['nama' => $namaPoli], ['deskripsi' => 'Layanan '.$namaPoli]);
        $user = User::factory()->role('dokter')->create(['name' => $nama]);

        $dokter = Doctor::query()->create([
            'user_id' => $user->id,
            'poli_id' => $poli->id,
            'no_str' => 'STR-'.Str::random(4),
            'spesialisasi' => 'Dokter Gigi',
            'tarif_konsultasi' => 35000,
        ]);

        DoctorSchedule::query()->create([
            'doctor_id' => $dokter->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'durasi_slot' => 30,
            'kuota' => 10,
            'is_active' => true,
        ]);

        return $dokter;
    }

    private function seninDepan(): string
    {
        return Carbon::parse('next monday')->toDateString();
    }

    /** @return \Illuminate\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface */
    private function responTeks(string $teks)
    {
        return Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => $teks], 'finish_reason' => 'stop']]], 200);
    }

    /** @return \Illuminate\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface */
    private function responTool(string $nama, array $argumen, string $id = 'call_1')
    {
        return Http::response(['choices' => [['message' => [
            'role' => 'assistant',
            'content' => null,
            'tool_calls' => [['id' => $id, 'type' => 'function', 'function' => ['name' => $nama, 'arguments' => json_encode($argumen)]]],
        ], 'finish_reason' => 'tool_calls']]], 200);
    }

    /** Model balas teks biasa (tanpa tool) untuk seluruh test. */
    private function fakeTeksSaja(string $teks): void
    {
        Http::fake(['*' => fn () => $this->responTeks($teks)]);
    }

    /**
     * Satu fake untuk seluruh percakapan dalam test.
     *
     * @param  array<int, array{kata: string, tool: string, argumen: array}>  $skenario
     *         'kata' = pemicu pada pesan pengguna terakhir; 'tool'/'argumen' yang dipanggil.
     */
    private function fakeSkrip(array $skenario, string $teksAkhir = 'Baik, ada lagi yang bisa dibantu?'): void
    {
        Http::fake(function ($request) use ($skenario, $teksAkhir) {
            $pesan = $request->data()['messages'] ?? [];

            // Sudah ada hasil tool di riwayat → model menjawab dengan teks.
            if (collect($pesan)->contains(fn ($m) => ($m['role'] ?? '') === 'tool')) {
                return $this->responTeks($teksAkhir);
            }

            $terakhirPengguna = '';
            foreach ($pesan as $m) {
                if (($m['role'] ?? '') === 'user') {
                    $terakhirPengguna = (string) ($m['content'] ?? '');
                }
            }

            foreach ($skenario as $bagian) {
                if (str_contains(Str::lower($terakhirPengguna), $bagian['kata'])) {
                    return $this->responTool($bagian['tool'], $bagian['argumen']);
                }
            }

            return $this->responTeks($teksAkhir);
        });
    }

    public function test_jawaban_model_disimpan_sebagai_pesan_bot(): void
    {
        $this->fakeTeksSaja('Halo, silakan tanya soal jadwal atau pendaftaran.');

        $this->actingAs($this->pasien())
            ->post(route('pasien.chat.kirim'), ['pesan' => 'halo'])
            ->assertRedirect(route('pasien.chat'));

        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'bot',
            'isi' => 'Halo, silakan tanya soal jadwal atau pendaftaran.',
            // 'ai' = jawaban model tanpa tool; 'faq' khusus jalur basis pengetahuan.
            'intent' => 'ai',
        ]);
    }

    public function test_tool_daftar_dokter_dijalankan_dan_intent_dicatat(): void
    {
        $this->dokter();

        $this->fakeSkrip([
            ['kata' => 'buka hari apa', 'tool' => 'daftar_dokter', 'argumen' => ['tanggal' => $this->seninDepan()]],
        ], 'drg. Beni praktik Senin.');

        $this->actingAs($this->pasien())
            ->post(route('pasien.chat.kirim'), ['pesan' => 'dokter gigi buka hari apa?']);

        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'bot',
            'intent' => 'tanya_jadwal',
        ]);
    }

    public function test_buat_janji_ditolak_kalau_belum_diverifikasi(): void
    {
        $dokter = $this->dokter();

        $this->fakeSkrip([
            ['kata' => 'buat', 'tool' => 'buat_janji', 'argumen' => [
                'dokter_id' => $dokter->id,
                'tanggal' => $this->seninDepan(),
                'jam' => '08:00',
                'keluhan' => 'sakit gigi',
            ]],
        ], 'Kartu QR kamu belum diverifikasi petugas, jadi janji belum bisa dibuat.');

        $this->actingAs($this->pasien(terverifikasi: false))
            ->post(route('pasien.chat.kirim'), ['pesan' => 'buatkan janji dokter gigi senin jam 8']);

        $this->assertDatabaseCount('visits', 0);
        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'bot',
            'intent' => 'buat_janji',
        ]);
    }

    public function test_buat_janji_tersimpan_kalau_sudah_diverifikasi_dan_slot_sesuai_tawaran(): void
    {
        $dokter = $this->dokter();
        $tanggal = $this->seninDepan();
        $pasien = $this->pasien();

        // Satu fake untuk dua putaran: putaran 1 menawarkan slot, putaran 2 mengonfirmasi.
        $this->fakeSkrip([
            ['kata' => 'kosong', 'tool' => 'cek_jadwal', 'argumen' => ['dokter_id' => $dokter->id, 'tanggal' => $tanggal]],
            ['kata' => 'setuju', 'tool' => 'buat_janji', 'argumen' => [
                'dokter_id' => $dokter->id,
                'tanggal' => $tanggal,
                'jam' => '08:30',
                'keluhan' => 'sakit gigi sejak kemarin',
            ]],
        ], 'Baik, janji kamu sudah tersimpan.');

        $this->actingAs($pasien)->post(route('pasien.chat.kirim'), ['pesan' => 'dokter gigi senin jam berapa yang kosong?']);

        $this->assertDatabaseCount('visits', 0);

        $this->actingAs($pasien)->post(route('pasien.chat.kirim'), ['pesan' => 'iya saya setuju, buatkan janjinya']);

        $visit = Visit::query()->first();

        $this->assertNotNull($visit, 'janji dibuat dari percakapan');
        $this->assertSame('chatbot', $visit->sumber);
        $this->assertSame('booking', $visit->status);
        $this->assertSame('08:30', substr((string) $visit->jam, 0, 5));
    }

    public function test_buat_janji_ditolak_kalau_slot_belum_pernah_ditawarkan(): void
    {
        $dokter = $this->dokter();

        // Model langsung memanggil buat_janji tanpa cek_jadwal lebih dulu.
        $this->fakeSkrip([
            ['kata' => 'langsung', 'tool' => 'buat_janji', 'argumen' => [
                'dokter_id' => $dokter->id,
                'tanggal' => $this->seninDepan(),
                'jam' => '08:30',
            ]],
        ], 'Maaf, saya perlu cek jadwal dulu sebelum membuat janji.');

        $this->actingAs($this->pasien())
            ->post(route('pasien.chat.kirim'), ['pesan' => 'langsung buatkan janji senin jam 8 pagi']);

        $this->assertDatabaseCount('visits', 0);
    }

    public function test_buat_janji_ditolak_kalau_dokter_berbeda_dari_tawaran(): void
    {
        $ditawarkan = $this->dokter();
        $lain = $this->dokter('dr. Ayu Lestari', 'Poli Umum');
        $tanggal = $this->seninDepan();
        $pasien = $this->pasien();

        $this->fakeSkrip([
            ['kata' => 'jadwal', 'tool' => 'cek_jadwal', 'argumen' => ['dokter_id' => $ditawarkan->id, 'tanggal' => $tanggal]],
            ['kata' => 'setuju', 'tool' => 'buat_janji', 'argumen' => ['dokter_id' => $lain->id, 'tanggal' => $tanggal, 'jam' => '08:30']],
        ], 'Maaf, saya ulangi cek jadwalnya dulu ya.');

        $this->actingAs($pasien)->post(route('pasien.chat.kirim'), ['pesan' => 'jadwal dokter gigi senin?']);

        // Pasien setuju, tapi model membuat janji ke dokter lain.
        $this->actingAs($pasien)->post(route('pasien.chat.kirim'), ['pesan' => 'ya setuju']);

        $this->assertDatabaseCount('visits', 0);
    }

    public function test_tawaran_slot_tidak_bisa_dipakai_dua_kali(): void
    {
        $dokter = $this->dokter();
        $tanggal = $this->seninDepan();
        $pasien = $this->pasien();

        $this->fakeSkrip([
            ['kata' => 'jadwal', 'tool' => 'cek_jadwal', 'argumen' => ['dokter_id' => $dokter->id, 'tanggal' => $tanggal]],
            ['kata' => 'setuju', 'tool' => 'buat_janji', 'argumen' => ['dokter_id' => $dokter->id, 'tanggal' => $tanggal, 'jam' => '08:30']],
            ['kata' => 'lagi', 'tool' => 'buat_janji', 'argumen' => ['dokter_id' => $dokter->id, 'tanggal' => $tanggal, 'jam' => '08:30']],
        ], 'Janji tersimpan.');

        $this->actingAs($pasien)->post(route('pasien.chat.kirim'), ['pesan' => 'jadwal senin?']);
        $this->actingAs($pasien)->post(route('pasien.chat.kirim'), ['pesan' => 'ya setuju']);

        $this->assertDatabaseCount('visits', 1);

        // Kirim ulang janji yang sama tanpa tawaran baru → ditolak.
        $this->actingAs($pasien)->post(route('pasien.chat.kirim'), ['pesan' => 'buat lagi jam yang sama']);

        $this->assertDatabaseCount('visits', 1);
    }

    public function test_gateway_model_mati_jatuh_ke_jawaban_faq(): void
    {
        ChatbotKnowledge::query()->create([
            'kategori' => 'Pendaftaran',
            'pertanyaan' => 'syarat daftar berobat apa saja',
            'jawaban' => 'Bawa kartu identitas dan nomor WhatsApp aktif.',
            'is_active' => true,
        ]);

        Http::fake(['*' => Http::response('gateway mati', 500)]);

        $this->actingAs($this->pasien())
            ->post(route('pasien.chat.kirim'), ['pesan' => 'syarat daftar berobat apa']);

        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'bot',
            'intent' => 'faq',
            'isi' => 'Bawa kartu identitas dan nomor WhatsApp aktif.',
        ]);
    }

    public function test_panel_janji_tampil_di_halaman_chat(): void
    {
        $dokter = $this->dokter();
        $tanggal = $this->seninDepan();
        $pasien = $this->pasien();

        $this->fakeSkrip([
            ['kata' => 'jadwal', 'tool' => 'cek_jadwal', 'argumen' => ['dokter_id' => $dokter->id, 'tanggal' => $tanggal]],
            ['kata' => 'setuju', 'tool' => 'buat_janji', 'argumen' => ['dokter_id' => $dokter->id, 'tanggal' => $tanggal, 'jam' => '09:00']],
        ], 'Janji tersimpan.');

        $this->actingAs($pasien)->post(route('pasien.chat.kirim'), ['pesan' => 'jadwal senin?']);
        $this->actingAs($pasien)->post(route('pasien.chat.kirim'), ['pesan' => 'ya setuju']);

        $this->assertDatabaseCount('visits', 1);

        $this->actingAs($pasien)
            ->get(route('pasien.chat'))
            ->assertOk()
            ->assertSee('Janji temu aktif')
            ->assertSee('drg. Beni Saputra');
    }
}
