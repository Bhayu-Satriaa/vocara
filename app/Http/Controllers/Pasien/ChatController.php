<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\AsistenChat;
use App\Services\BookingService;
use App\Services\ChatbotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Chat teks pasien dengan chatbot (dijawab model 9Router, cadangan FAQ).
 * Rancangan: docs/SPEC-CHATBOT.md dan docs/SPEC-BOOKING-CHATBOT.md
 */
class ChatController extends Controller
{
    /** Kunci sesi tempat ID percakapan yang sedang dipakai disimpan. */
    private const KUNCI_SESI = 'chat_conversation_id';

    /** Halaman chat: percakapan sesi ini + riwayat pesannya + janji aktif pasien. */
    public function index(Request $request): View
    {
        $percakapan = $this->percakapanAktif($request);
        $pasien = $request->user()->profilAktif();

        return view('pasien.chat', [
            'percakapan' => $percakapan,
            'pesan' => $percakapan->messages()->orderBy('id')->get(),
            'pasien' => $pasien,
            'janji' => $pasien ? app(BookingService::class)->janjiMilik($pasien) : collect(),
        ]);
    }

    /**
     * Simpan pertanyaan pasien, lalu jawab: lewat model AI kalau gateway hidup,
     * kalau tidak jatuh ke jawaban FAQ dari basis pengetahuan.
     */
    public function kirim(Request $request, ChatbotService $chatbot, AsistenChat $asisten): RedirectResponse
    {
        $data = $request->validate(
            ['pesan' => ['required', 'string', 'max:500']],
            [
                'pesan.required' => 'Tulis pertanyaanmu dulu.',
                'pesan.max' => 'Pertanyaan terlalu panjang — maksimal 500 karakter.',
            ],
        );

        $percakapan = $this->percakapanAktif($request);

        ChatMessage::query()->create([
            'conversation_id' => $percakapan->id,
            'pengirim' => 'pengguna',
            'isi' => $data['pesan'],
            'tipe_input' => 'teks',
        ]);

        $hasil = $asisten->balas($percakapan, $request->user()->profilAktif());

        if ($hasil === null) {
            $faq = $chatbot->cariJawaban($data['pesan']);
            $hasil = [
                'isi' => $faq?->jawaban ?? ChatbotService::JAWABAN_TIDAK_DIKENAL,
                'intent' => $faq ? 'faq' : 'tidak_dikenal',
            ];
        }

        ChatMessage::query()->create([
            'conversation_id' => $percakapan->id,
            'pengirim' => 'bot',
            'isi' => $hasil['isi'],
            'tipe_input' => 'teks',
            'intent' => $hasil['intent'],
        ]);

        return redirect()->route('pasien.chat');
    }

    /**
     * Tutup percakapan sesi ini dan mulai yang baru.
     * Riwayat lama TIDAK dihapus — hanya tidak ditampilkan lagi di layar pasien.
     */
    public function baru(Request $request): RedirectResponse
    {
        $this->percakapanAktif($request)->update(['ended_at' => now()]);

        $request->session()->forget(self::KUNCI_SESI);

        return redirect()->route('pasien.chat');
    }

    /**
     * Percakapan teks untuk SESI INI.
     *
     * Percakapan diikat ke sesi login (bukan selalu percakapan terbuka terakhir): tiap
     * sesi baru mulai dengan layar kosong, sehingga riwayat uji/coba-coba tidak menumpuk
     * di layar pasien. Riwayat tetap tersimpan di `chat_messages` untuk penelusuran.
     */
    private function percakapanAktif(Request $request): ChatConversation
    {
        $user = $request->user();
        $id = $request->session()->get(self::KUNCI_SESI);

        if ($id) {
            $percakapan = ChatConversation::query()
                ->whereKey($id)
                ->where('user_id', $user->id)
                ->where('mode', 'teks')
                ->whereNull('ended_at')
                ->first();

            if ($percakapan) {
                return $percakapan;
            }
        }

        $percakapan = ChatConversation::query()->create([
            'user_id' => $user->id,
            'patient_id' => $user->profilAktif()?->id,
            'mode' => 'teks',
            'started_at' => now(),
        ]);

        $request->session()->put(self::KUNCI_SESI, $percakapan->id);

        return $percakapan;
    }
}
