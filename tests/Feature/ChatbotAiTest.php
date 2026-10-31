<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Poli;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Chatbot dengan otak model (tool calling) — Fase 3 docs/SPEC-BOOKING-CHATBOT.md.
 * Gateway model dipalsukan lewat Http::fake supaya test tidak butuh 9Router hidup.
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

    private function dokter(): Doctor
    {
        $poli = Poli::query()->firstOrCreate(['nama' => 'Poli Gigi'], ['deskripsi' => 'Perawatan gigi']);
        $user = User::factory()->role('dokter')->create(['name' => 'drg. Beni Saputra']);

        $dokter = Doctor::query()->create([
            'user_id' => $user->id,
            'poli_id' => $poli->id,
            'no_str' => 'STR-GIGI',
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

    /** Balasan model tanpa tool: jawaban teks biasa. */
    private function balasTeks(string $teks): void
    {
        Http::fake([
            '*' => Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => $teks]]]], 200),
        ]);
    }

    /** Balasan model berurutan: panggil tool dulu, lalu jawab. */
    private function balasDenganTool(array $toolCalls, string $jawabanAkhir): void
    {
        $urutan = 0;

        Http::fake(function () use (&$urutan, $toolCalls, $jawabanAkhir) {
            $urutan++;

            if ($urutan === 1) {
                return Http::response(['choices' => [['message' => [
                    'role' => 'assistant',
                    'content' => null,
                    'tool_calls' => $toolCalls,
                ]]]], 200);
            }

            return Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => $jawabanAkhir]]]], 200);
        });
    }

    private function panggilTool(string $nama, array $argumen, string $id = 'call_1'): array
    {
        return [['id' => $id, 'type' => 'function', 'function' => ['name' => $nama, 'arguments' => json_encode($argumen)]]];
    }

    public function test_jawaban_model_disimpan_sebagai_pesan_bot(): void
    {
        $this->balasTeks('Halo, silakan tanya soal jadwal atau pendaftaran.');

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
        $this->balasDenganTool(
            $this->panggilTool('daftar_dokter', ['tanggal' => $this->seninDepan()]),
            'Ada drg. Beni Saputra di Poli Gigi hari Senin. Mau saya carikan jamnya?',
        );

        $this->actingAs($this->pasien())
            ->post(route('pasien.chat.kirim'), ['pesan' => 'dokter gigi buka hari apa']);

        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'bot',
            'intent' => 'tanya_jadwal',
        ]);
    }

    public function test_buat_janji_ditolak_kalau_belum_diverifikasi(): void
    {
        $dokter = $this->dokter();
        $this->balasDenganTool(
            $this->panggilTool('buat_janji', [
                'dokter_id' => $dokter->id,
                'tanggal' => $this->seninDepan(),
                'jam' => '08:00',
                'keluhan' => 'sakit gigi',
            ]),
            'Kartu QR kamu belum diverifikasi petugas, jadi janji belum bisa dibuat.',
        );

        $this->actingAs($this->pasien(terverifikasi: false))
            ->post(route('pasien.chat.kirim'), ['pesan' => 'buatkan janji dokter gigi senin jam 8']);

        $this->assertDatabaseCount('visits', 0);
        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'bot',
            'intent' => 'buat_janji',
        ]);
    }

    public function test_buat_janji_tersimpan_kalau_sudah_diverifikasi(): void
    {
        $dokter = $this->dokter();
        $tanggal = $this->seninDepan();

        $this->balasDenganTool(
            $this->panggilTool('buat_janji', [
                'dokter_id' => $dokter->id,
                'tanggal' => $tanggal,
                'jam' => '08:30',
                'keluhan' => 'sakit gigi sejak kemarin',
            ]),
            'Baik, janji kamu Senin jam 08:30 dengan drg. Beni sudah tersimpan.',
        );

        $this->actingAs($this->pasien())
            ->post(route('pasien.chat.kirim'), ['pesan' => 'iya saya setuju, buatkan janjinya']);

        $visit = Visit::query()->first();

        $this->assertNotNull($visit, 'janji dibuat dari percakapan');
        $this->assertSame('chatbot', $visit->sumber);
        $this->assertSame('booking', $visit->status);
        $this->assertSame('08:30', substr((string) $visit->jam, 0, 5));
    }

    public function test_gateway_model_mati_jatuh_ke_jawaban_faq(): void
    {
        \App\Models\ChatbotKnowledge::query()->create([
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

        $this->balasDenganTool(
            $this->panggilTool('buat_janji', ['dokter_id' => $dokter->id, 'tanggal' => $tanggal, 'jam' => '09:00']),
            'Janji tersimpan.',
        );

        $pasien = $this->pasien();

        $this->actingAs($pasien)->post(route('pasien.chat.kirim'), ['pesan' => 'buat janji jam 9']);

        $this->actingAs($pasien)
            ->get(route('pasien.chat'))
            ->assertOk()
            ->assertSee('Janji temu aktif')
            ->assertSee('drg. Beni Saputra');
    }
}
