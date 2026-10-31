<?php

namespace Tests\Feature;

use App\Filament\Resources\ChatbotKnowledges\Pages\CreateKnowledge;
use App\Models\ChatbotKnowledge;
use App\Models\Patient;
use App\Models\User;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Chatbot dasar Tahap 1: menjawab dari basis pengetahuan tanpa memanggil AI.
 * Rancangan: docs/SPEC-CHATBOT.md
 */
class ChatbotFaqTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_admin_bisa_menambah_pengetahuan_dari_panel(): void
    {
        $admin = User::factory()->role('admin')->create();
        $this->actingAs($admin);

        Livewire::test(CreateKnowledge::class)
            ->fillForm([
                'kategori' => 'Biaya',
                'pertanyaan' => 'berapa biaya konsultasi dokter umum',
                'jawaban' => 'Tarif konsultasi mengikuti dokter yang dipilih.',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $baris = ChatbotKnowledge::query()->where('kategori', 'Biaya')->first();

        $this->assertNotNull($baris, 'pengetahuan baru tersimpan');
        $this->assertSame($admin->id, $baris->updated_by, 'pelaku pengisian dicatat');
    }

    public function test_admin_bisa_membuka_kelola_pengetahuan(): void
    {
        $this->actingAs(User::factory()->role('admin')->create())
            ->get('/admin/chatbot-knowledge')
            ->assertOk();
    }

    public function test_staf_di_luar_admin_tidak_bisa_membuka_kelola_pengetahuan(): void
    {
        $respon = $this->actingAs(User::factory()->role('petugas')->create())
            ->get('/admin/chatbot-knowledge');

        // Filament bisa menjawab 403 atau menyembunyikan resource-nya dengan 404 —
        // yang penting bukan 200 dan isinya tidak bocor.
        $this->assertContains($respon->getStatusCode(), [403, 404], 'staf non-admin tidak boleh masuk');
        $respon->assertDontSee('basis pengetahuan');
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
