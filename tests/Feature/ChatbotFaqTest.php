<?php

namespace Tests\Feature;

use App\Models\ChatbotKnowledge;
use App\Models\ChatConversation;
use App\Models\Patient;
use App\Models\User;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chatbot dasar Tahap 1: menjawab dari basis pengetahuan tanpa memanggil AI.
 * Rancangan: docs/SPEC-CHATBOT.md
 */
class ChatbotFaqTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test ini soal jalur FAQ, bukan AI. Matikan alamat gateway supaya tidak pernah
     * menembak 9Router sungguhan — kalau .env punya kunci, jawaban model akan menimpa
     * jawaban FAQ dan test ini gagal karena alasan yang salah.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.llm.base_url' => null]);
    }

    /** Pasien dengan data diri (profil lengkap), siap memakai halaman chat. */
    private function pasien(string $email = 'pasien@klinik.test'): User
    {
        /** @var User $user */
        $user = User::factory()->pasien()->create(['email' => $email]);

        Patient::query()->create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Budi Santoso',
            'no_wa' => '081234567890',
            'tgl_lahir' => '1995-05-20',
            'jenis_kelamin' => 'L',
        ]);

        return $user->refresh();
    }

    private function pengetahuan(bool $aktif = true): ChatbotKnowledge
    {
        return ChatbotKnowledge::query()->create([
            'kategori' => 'Pendaftaran',
            'pertanyaan' => 'syarat daftar berobat apa saja',
            'jawaban' => 'Bawa KTP dan nomor WhatsApp aktif, petugas membuatkan kartu QR.',
            'is_active' => $aktif,
        ]);
    }

    public function test_admin_bisa_menambah_pengetahuan_dari_dashboard(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)
            ->post(route('admin.pengetahuan-chatbot.store'), [
                'kategori' => 'Biaya',
                'pertanyaan' => 'berapa biaya konsultasi dokter umum',
                'jawaban' => 'Tarif konsultasi mengikuti dokter yang dipilih.',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.pengetahuan-chatbot.index'));

        $baris = ChatbotKnowledge::query()->where('kategori', 'Biaya')->first();

        $this->assertNotNull($baris, 'pengetahuan baru tersimpan');
        $this->assertSame($admin->id, $baris->updated_by, 'pelaku pengisian dicatat');
    }

    public function test_admin_bisa_membuka_kelola_pengetahuan(): void
    {
        $this->actingAs(User::factory()->role('admin')->create())
            ->get(route('admin.pengetahuan-chatbot.index'))
            ->assertOk()
            ->assertSee('Pengetahuan chatbot');
    }

    public function test_staf_di_luar_admin_tidak_bisa_membuka_kelola_pengetahuan(): void
    {
        $this->actingAs(User::factory()->role('petugas')->create())
            ->get(route('admin.pengetahuan-chatbot.index'))
            ->assertForbidden();
    }

    public function test_pertanyaan_cocok_dijawab_dari_basis_pengetahuan(): void
    {
        $this->pengetahuan();
        $pasien = $this->pasien();

        $this->actingAs($pasien)
            ->post(route('pasien.chat.kirim'), ['pesan' => 'syarat daftar berobat apa'])
            ->assertRedirect(route('pasien.chat'));

        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'pengguna',
            'isi' => 'syarat daftar berobat apa',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'bot',
            'intent' => 'faq',
            'isi' => 'Bawa KTP dan nomor WhatsApp aktif, petugas membuatkan kartu QR.',
        ]);
    }

    public function test_pengetahuan_nonaktif_tidak_dipakai(): void
    {
        $this->pengetahuan(aktif: false);

        $this->actingAs($this->pasien())
            ->post(route('pasien.chat.kirim'), ['pesan' => 'syarat daftar berobat apa'])
            ->assertRedirect(route('pasien.chat'));

        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'bot',
            'intent' => 'tidak_dikenal',
            'isi' => ChatbotService::JAWABAN_TIDAK_DIKENAL,
        ]);
    }

    public function test_pertanyaan_tidak_dikenal_dijawab_baku(): void
    {
        $this->pengetahuan();
        $pasien = $this->pasien();

        $this->actingAs($pasien)
            ->post(route('pasien.chat.kirim'), ['pesan' => 'cara memperbaiki motor mati'])
            ->assertRedirect(route('pasien.chat'));

        $this->assertDatabaseHas('chat_messages', [
            'pengirim' => 'bot',
            'intent' => 'tidak_dikenal',
        ]);

        $this->actingAs($pasien)
            ->get(route('pasien.chat'))
            ->assertOk()
            ->assertSee('belum punya jawaban')
            ->assertSee('pengganti pemeriksaan dokter');
    }

    public function test_percakapan_dicatat_untuk_profil_yang_sedang_dipakai(): void
    {
        $this->pengetahuan();
        $pasien = $this->pasien();

        $this->actingAs($pasien)
            ->post(route('pasien.chat.kirim'), ['pesan' => 'syarat daftar berobat apa']);

        $this->assertDatabaseHas('chat_conversations', [
            'user_id' => $pasien->id,
            'patient_id' => $pasien->profilAktif()->id,
            'mode' => 'teks',
        ]);
    }

    public function test_percakapan_dimulai_kosong_setiap_sesi(): void
    {
        $this->pengetahuan();
        $pasien = $this->pasien();

        $this->actingAs($pasien)
            ->post(route('pasien.chat.kirim'), ['pesan' => 'syarat daftar berobat apa']);

        // Masih di sesi yang sama: pertanyaan & jawabannya tampil (konteks terjaga).
        $this->actingAs($pasien)
            ->get(route('pasien.chat'))
            ->assertOk()
            ->assertSee('Bawa KTP dan nomor WhatsApp aktif');

        // Sesi login baru (pasien keluar lalu masuk lagi): layar mulai kosong.
        $this->flushSession();

        $this->actingAs($pasien)
            ->get(route('pasien.chat'))
            ->assertOk()
            ->assertDontSee('Bawa KTP dan nomor WhatsApp aktif');

        $this->assertSame(2, ChatConversation::query()->count(), 'sesi baru dapat percakapan baru');
        $this->assertDatabaseCount('chat_messages', 2); // riwayat lama tetap tersimpan
    }

    public function test_tombol_percakapan_baru_mengosongkan_layar_dan_menutup_percakapan(): void
    {
        $this->pengetahuan();
        $pasien = $this->pasien();

        $this->actingAs($pasien)
            ->post(route('pasien.chat.kirim'), ['pesan' => 'syarat daftar berobat apa']);

        $this->actingAs($pasien)
            ->post(route('pasien.chat.baru'))
            ->assertRedirect(route('pasien.chat'));

        $this->actingAs($pasien)
            ->get(route('pasien.chat'))
            ->assertOk()
            ->assertDontSee('Bawa KTP dan nomor WhatsApp aktif');

        $this->assertNotNull(ChatConversation::query()->orderBy('id')->first()->ended_at);
        $this->assertSame(2, ChatConversation::query()->count());
        $this->assertDatabaseCount('chat_messages', 2); // riwayat lama tidak dihapus
    }

    public function test_riwayat_percakapan_tidak_bisa_dibaca_pasien_lain(): void
    {
        $this->pengetahuan();
        $pemilik = $this->pasien('pemilik@klinik.test');
        $lain = $this->pasien('lain@klinik.test');

        $this->actingAs($pemilik)
            ->post(route('pasien.chat.kirim'), ['pesan' => 'syarat daftar berobat apa']);

        $this->actingAs($lain)
            ->get(route('pasien.chat'))
            ->assertOk()
            ->assertDontSee('syarat daftar berobat apa');
    }

    public function test_pesan_kosong_dan_terlalu_panjang_ditolak(): void
    {
        $pasien = $this->pasien();

        $this->actingAs($pasien)
            ->post(route('pasien.chat.kirim'), ['pesan' => ''])
            ->assertSessionHasErrors('pesan');

        $this->actingAs($pasien)
            ->post(route('pasien.chat.kirim'), ['pesan' => str_repeat('a', 501)])
            ->assertSessionHasErrors('pesan');

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_halaman_chat_butuh_profil_lengkap(): void
    {
        $tanpaProfil = User::factory()->pasien()->create(['email' => 'baru@klinik.test']);

        $this->actingAs($tanpaProfil)
            ->get(route('pasien.chat'))
            ->assertRedirect(route('pasien.profil'))
            ->assertSessionHas('peringatan');
    }
}
