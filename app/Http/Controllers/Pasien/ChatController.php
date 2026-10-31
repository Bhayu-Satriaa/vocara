<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\AsistenChat;
use App\Services\BookingService;
use App\Services\ChatbotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Chat teks pasien dengan chatbot dasar (Tahap 1: tanpa AI).
 * Rancangan: docs/SPEC-CHATBOT.md
 */
class ChatController extends Controller
{
    /** Halaman chat: percakapan aktif + riwayat pesannya + janji aktif pasien. */
    public function index(Request $request): View
    {
        $percakapan = $this->percakapanAktif($request->user());
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

        $percakapan = $this->percakapanAktif($request->user());

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

    /** Percakapan teks yang masih terbuka milik akun ini, atau dibuat baru. */
    private function percakapanAktif(User $user): ChatConversation
    {
        return ChatConversation::query()
            ->where('user_id', $user->id)
            ->where('mode', 'teks')
            ->whereNull('ended_at')
            ->latest('id')
            ->first()
            ?? ChatConversation::query()->create([
                'user_id' => $user->id,
                'patient_id' => $user->profilAktif()?->id,
                'mode' => 'teks',
                'started_at' => now(),
            ]);
    }
}
