<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
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
    /** Halaman chat: percakapan aktif + riwayat pesannya. */
    public function index(Request $request): View
    {
        $percakapan = $this->percakapanAktif($request->user());

        return view('pasien.chat', [
            'percakapan' => $percakapan,
            'pesan' => $percakapan->messages()->orderBy('id')->get(),
        ]);
    }

    /** Simpan pertanyaan pasien, lalu jawab dari basis pengetahuan. */
    public function kirim(Request $request, ChatbotService $chatbot): RedirectResponse
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

        $jawaban = $chatbot->cariJawaban($data['pesan']);

        ChatMessage::query()->create([
            'conversation_id' => $percakapan->id,
            'pengirim' => 'bot',
            'isi' => $jawaban?->jawaban ?? ChatbotService::JAWABAN_TIDAK_DIKENAL,
            'tipe_input' => 'teks',
            'intent' => $jawaban ? 'faq' : 'tidak_dikenal',
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
