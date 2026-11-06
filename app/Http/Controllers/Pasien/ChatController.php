<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\AsistenChat;
use App\Services\BookingService;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    public function kirim(Request $request, ChatbotService $chatbot, AsistenChat $asisten): RedirectResponse|JsonResponse
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

            // Model tidak menjawab. Kalau modelnya memang tidak diaktifkan, "tidak dikenal"
            // sudah tepat; kalau model ada tapi gagal dihubungi, katakan jujur sedang sibuk.
            $baku = $asisten->tersedia() ? ChatbotService::JAWABAN_SIBUK : ChatbotService::JAWABAN_TIDAK_DIKENAL;

            $hasil = [
                'isi' => $faq?->jawaban ?? $baku,
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

        // Halaman chat memanggil lewat fetch dan mengharapkan JSON: tanpa muat ulang
        // halaman, jadi tidak ada spinner di tab dan posisi scroll tidak melompat.
        if ($request->wantsJson()) {
            return response()->json([
                'jawaban' => $hasil['isi'],
                'intent' => $hasil['intent'],
            ]);
        }

        return redirect()->route('pasien.chat');
    }

    /**
     * Jawab pertanyaan pasien dengan cara dialirkan (SSE).
     *
     * Dipakai halaman chat lewat fetch: teks muncul bertahap, halaman tidak dimuat
     * ulang — jadi tidak ada spinner di tab dan posisi scroll tidak melompat.
     */
    public function alir(Request $request, ChatbotService $chatbot): StreamedResponse
    {
        $data = $request->validate(
            ['pesan' => ['required', 'string', 'max:500']],
            [
                'pesan.required' => 'Tulis pertanyaanmu dulu.',
                'pesan.max' => 'Pertanyaan terlalu panjang — maksimal 500 karakter.',
            ],
        );

        $percakapan = $this->percakapanAktif($request);
        $pasien = $request->user()->profilAktif();
        $levelBuffer = ob_get_level();

        ChatMessage::query()->create([
            'conversation_id' => $percakapan->id,
            'pengirim' => 'pengguna',
            'isi' => $data['pesan'],
            'tipe_input' => 'teks',
        ]);

        return response()->stream(function () use ($chatbot, $percakapan, $pasien, $data, $levelBuffer) {
            ignore_user_abort(true);

            $teks = '';
            $intent = 'ai';
            $asisten = app(AsistenChat::class);

            foreach ($asisten->alirBalas($percakapan, $pasien) as $kejadian) {
                if (isset($kejadian['delta'])) {
                    $teks .= $kejadian['delta'];

                    echo $this->peristiwaSse(['delta' => $kejadian['delta']]);
                } elseif (isset($kejadian['alat'])) {
                    echo $this->peristiwaSse(['alat' => $kejadian['alat']]);
                } elseif (isset($kejadian['selesai'])) {
                    $intent = $kejadian['intent'];
                }

                $this->kurasBuffer($levelBuffer);
            }

            if (trim($teks) === '') {
                // Gateway bermasalah → jawab dari basis pengetahuan seperti jalur biasa,
                // dan kalau tidak ada yang cocok, katakan jujur bahwa asisten sedang sibuk.
                $faq = $chatbot->cariJawaban($data['pesan']);
                $baku = $asisten->tersedia() ? ChatbotService::JAWABAN_SIBUK : ChatbotService::JAWABAN_TIDAK_DIKENAL;
                $teks = $faq?->jawaban ?? $baku;
                $intent = $faq ? 'faq' : 'tidak_dikenal';

                echo $this->peristiwaSse(['delta' => $teks]);
                $this->kurasBuffer($levelBuffer);
            }

            ChatMessage::query()->create([
                'conversation_id' => $percakapan->id,
                'pengirim' => 'bot',
                'isi' => $teks,
                'tipe_input' => 'teks',
                'intent' => $intent,
            ]);

            echo $this->peristiwaSse(['selesai' => true, 'intent' => $intent]);
            echo "data: [DONE]\n\n";

            $this->kurasBuffer($levelBuffer);
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /** Satu baris peristiwa Server-Sent Events. */
    private function peristiwaSse(array $muatan): string
    {
        return 'data: '.json_encode($muatan, JSON_UNESCAPED_UNICODE)."\n\n";
    }

    /**
     * Kirim tiap potongan segera — jangan ditahan buffer PHP.
     *
     * Hanya buffer DI ATAS level yang tercatat saat permintaan masuk yang dibuang,
     * supaya penangkapan output milik framework (mis. saat test) tetap utuh.
     */
    private function kurasBuffer(int $levelAwal = 0): void
    {
        if (app()->runningUnitTests()) {
            return; // output sedang ditangkap framework test — jangan dikuras
        }

        while (ob_get_level() > $levelAwal) {
            ob_end_flush();
        }

        flush();
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
