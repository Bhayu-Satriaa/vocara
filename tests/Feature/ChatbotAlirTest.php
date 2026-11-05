<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
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
 * Jalur streaming chatbot (SSE): teks mengalir bertahap, halaman tidak dimuat ulang.
 * Gateway model dipalsukan supaya test tidak butuh 9Router hidup.
 *
 * Catatan: `Http::fake()` MENGGABUNG stub, jadi satu test hanya mendaftarkan SATU fake.
 */
class ChatbotAlirTest extends TestCase
{
    use RefreshDatabase;

    private function pasien(bool $terverifikasi = true): User
    {
        /** @var User $user */
        $user = User::factory()->pasien()->create(['email' => 'pasien@klinik.test']);

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

    private function dokter(): Doctor
    {
        $poli = Poli::query()->firstOrCreate(['nama' => 'Poli Umum'], ['deskripsi' => 'Layanan umum']);
        $user = User::factory()->role('dokter')->create(['name' => 'dr. Ayu Lestari']);

        $dokter = Doctor::query()->create([
            'user_id' => $user->id,
            'poli_id' => $poli->id,
            'no_str' => 'STR-'.Str::random(4),
            'spesialisasi' => 'Dokter Umum',
            'tarif_konsultasi' => 25000,
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

    /** Badan SSE palsu: beberapa potongan teks lalu penutup. */
    private function sseTeks(array $potongan): string
    {
        $badan = '';

        foreach ($potongan as $teks) {
            $badan .= 'data: '.json_encode(['choices' => [['delta' => ['content' => $teks]]]])."\n\n";
        }

        return $badan."data: [DONE]\n\n";
    }

    /** Badan SSE palsu yang memanggil satu tool (argumen dikirim bertahap seperti aslinya). */
    private function sseTool(string $nama, array $argumen): string
    {
        $json = json_encode($argumen);
        $separuh = (int) ceil(strlen($json) / 2);

        $potongan = [
            ['choices' => [['delta' => ['tool_calls' => [[
                'index' => 0,
                'id' => 'call_1',
                'type' => 'function',
                'function' => ['name' => $nama, 'arguments' => substr($json, 0, $separuh)],
            ]]]]]],
            ['choices' => [['delta' => ['tool_calls' => [[
                'index' => 0,
                'function' => ['arguments' => substr($json, $separuh)],
            ]]]]]],
        ];

        $badan = '';

        foreach ($potongan as $isi) {
            $badan .= 'data: '.json_encode($isi)."\n\n";
        }

        return $badan."data: [DONE]\n\n";
    }

    /** Fake berurutan: ronde ke-1 tool, ronde ke-2 teks. */
    private function fakeAlur(string $badanTool, string $badanTeks): void
    {
        $urutan = 0;

        Http::fake(function () use (&$urutan, $badanTool, $badanTeks) {
            $urutan++;

            return Http::response($urutan === 1 ? $badanTool : $badanTeks, 200, [
                'Content-Type' => 'text/event-stream',
            ]);
        });
    }

    public function test_jawaban_dialirkan_bertahap_dan_tersimpan(): void
    {
        Http::fake(['*' => fn () => Http::response(
            $this->sseTeks(['Halo! ', 'Syarat daftar: ', 'bawa KTP.']),
            200,
            ['Content-Type' => 'text/event-stream'],
        )]);

        $jawab = $this->actingAs($this->pasien())
            ->post(route('pasien.chat.alir'), ['pesan' => 'syarat daftar apa?'], ['Accept' => 'text/event-stream']);

        $jawab->assertOk();
        $jawab->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');

        $isi = $jawab->streamedContent();

        $this->assertStringContainsString('"delta":"Halo! "', $isi);
        $this->assertStringContainsString('"delta":"bawa KTP."', $isi);
        $this->assertStringContainsString('"selesai":true', $isi);
        $this->assertStringContainsString('"intent":"ai"', $isi);

        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'bot',
            'isi' => 'Halo! Syarat daftar: bawa KTP.',
            'intent' => 'ai',
        ]);
    }

    public function test_tool_di_jalur_streaming_dijalankan_lalu_jawaban_dialirkan(): void
    {
        $dokter = $this->dokter();
        $tanggal = $this->seninDepan();
        $pasien = $this->pasien();

        $this->fakeAlur(
            $this->sseTool('cek_jadwal', ['dokter_id' => $dokter->id, 'tanggal' => $tanggal]),
            $this->sseTeks(['Senin jam ', '08:30 tersedia.']),
        );

        $jawab = $this->actingAs($pasien)
            ->post(route('pasien.chat.alir'), ['pesan' => 'dokter umum senin jam berapa yang kosong?'], ['Accept' => 'text/event-stream']);

        $isi = $jawab->streamedContent();

        $this->assertStringContainsString('"alat":"cek_jadwal"', $isi);
        $this->assertStringContainsString('"delta":"08:30 tersedia."', $isi);
        $this->assertStringContainsString('"intent":"tanya_jadwal"', $isi);

        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'bot',
            'isi' => 'Senin jam 08:30 tersedia.',
            'intent' => 'tanya_jadwal',
        ]);
    }

    public function test_janji_bisa_dibuat_lewat_jalur_streaming(): void
    {
        $dokter = $this->dokter();
        $tanggal = $this->seninDepan();
        $pasien = $this->pasien();

        // Satu fake untuk dua putaran (Http::fake menggabung stub, bukan menimpa):
        // putaran 1 model menawarkan slot, putaran 2 pasien menyetujui.
        Http::fake(function ($request) use ($dokter, $tanggal) {
            $pesan = collect($request->data()['messages'] ?? []);
            $terakhirPengguna = (string) ($pesan->last(fn ($m) => ($m['role'] ?? '') === 'user')['content'] ?? '');
            $adaHasilTool = $pesan->contains(fn ($m) => ($m['role'] ?? '') === 'tool');

            if ($adaHasilTool) {
                return Http::response($this->sseTeks(['Janji kamu tersimpan.']), 200, ['Content-Type' => 'text/event-stream']);
            }

            return str_contains(Str::lower($terakhirPengguna), 'setuju')
                ? Http::response($this->sseTool('buat_janji', [
                    'dokter_id' => $dokter->id,
                    'tanggal' => $tanggal,
                    'jam' => '08:30',
                    'keluhan' => 'pusing',
                ]), 200, ['Content-Type' => 'text/event-stream'])
                : Http::response($this->sseTool('cek_jadwal', [
                    'dokter_id' => $dokter->id,
                    'tanggal' => $tanggal,
                ]), 200, ['Content-Type' => 'text/event-stream']);
        });

        $this->actingAs($pasien)
            ->post(route('pasien.chat.alir'), ['pesan' => 'dokter umum senin jam berapa yang kosong?'], ['Accept' => 'text/event-stream'])
            ->assertOk()
            ->streamedContent();

        $this->assertDatabaseCount('visits', 0);

        // Ronde berikutnya (sesi masih sama): pasien setuju.
        $this->actingAs($pasien)
            ->post(route('pasien.chat.alir'), ['pesan' => 'ya setuju'], ['Accept' => 'text/event-stream'])
            ->assertOk()
            ->streamedContent();

        $visit = Visit::query()->first();

        $this->assertNotNull($visit, 'janji dibuat lewat jalur streaming');
        $this->assertSame('chatbot', $visit->sumber);
        $this->assertSame('08:30', substr((string) $visit->jam, 0, 5));

        $this->assertDatabaseHas('chat_messages', ['pengirim' => 'bot', 'intent' => 'buat_janji']);
    }

    public function test_gateway_mati_dialirkan_jawaban_faq(): void
    {
        \App\Models\ChatbotKnowledge::query()->create([
            'kategori' => 'Pendaftaran',
            'pertanyaan' => 'syarat daftar berobat apa saja',
            'jawaban' => 'Bawa kartu identitas dan nomor WhatsApp aktif.',
            'is_active' => true,
        ]);

        Http::fake(['*' => Http::response('gateway mati', 500)]);

        $isi = $this->actingAs($this->pasien())
            ->post(route('pasien.chat.alir'), ['pesan' => 'syarat daftar berobat apa'], ['Accept' => 'text/event-stream'])
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Bawa kartu identitas dan nomor WhatsApp aktif.', $isi);
        $this->assertStringContainsString('"intent":"faq"', $isi);

        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'bot',
            'intent' => 'faq',
            'isi' => 'Bawa kartu identitas dan nomor WhatsApp aktif.',
        ]);
    }

    public function test_halaman_chat_memuat_skrip_loader_di_dalam_chat(): void
    {
        $pasien = $this->pasien();

        $this->actingAs($pasien)
            ->get(route('pasien.chat'))
            ->assertOk()
            ->assertSee('id="kotak-chat"', false)
            ->assertSee('id="form-chat"', false)
            ->assertSee('menyusun jawaban', false)          // loader DI DALAM chat
            ->assertSee("form.action", false)               // kirim lewat fetch, bukan muat ulang
            ->assertDontSee('data-alir="'.route('pasien.chat.alir').'"', false); // streaming mati (bawaan)
    }

    public function test_jalur_streaming_bisa_dinyalakan_lewat_konfigurasi(): void
    {
        config(['services.llm.chat_stream' => true]);

        $this->actingAs($this->pasien())
            ->get(route('pasien.chat'))
            ->assertOk()
            ->assertSee('data-alir="'.route('pasien.chat.alir').'"', false);
    }

    public function test_jawaban_json_dipakai_halaman_chat(): void
    {
        // Jalur biasa (bukan streaming): gateway membalas JSON utuh, bukan SSE.
        Http::fake(['*' => fn () => Http::response([
            'choices' => [['message' => ['role' => 'assistant', 'content' => 'Syaratnya bawa KTP.'], 'finish_reason' => 'stop']],
        ], 200)]);

        $this->actingAs($this->pasien())
            ->postJson(route('pasien.chat.kirim'), ['pesan' => 'syarat daftar apa?'])
            ->assertOk()
            ->assertJson(['jawaban' => 'Syaratnya bawa KTP.', 'intent' => 'ai']);
    }

    public function test_percakapan_tetap_satu_untuk_beberapa_pertanyaan(): void
    {
        Http::fake(['*' => fn () => Http::response($this->sseTeks(['Baik.']), 200, ['Content-Type' => 'text/event-stream'])]);

        $pasien = $this->pasien();

        // Catatan: callback StreamedResponse baru berjalan saat respons dikirim — di test
        // itu berarti `streamedContent()` wajib dipanggil, kalau tidak pesan bot tidak tersimpan.
        $this->actingAs($pasien)
            ->post(route('pasien.chat.alir'), ['pesan' => 'halo satu'], ['Accept' => 'text/event-stream'])
            ->assertOk()
            ->streamedContent();

        $this->actingAs($pasien)
            ->post(route('pasien.chat.alir'), ['pesan' => 'halo dua'], ['Accept' => 'text/event-stream'])
            ->assertOk()
            ->streamedContent();

        $this->assertSame(1, ChatConversation::query()->count());
        $this->assertSame(4, ChatMessage::query()->count());
    }
}
