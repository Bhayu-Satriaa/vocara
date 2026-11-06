@extends('layouts.app')

@section('judul', 'Tanya Chatbot')

@section('isi')
    <div class="mx-auto max-w-2xl">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-ink">Tanya chatbot</h1>
                <p class="mt-1 text-sm text-muted">
                    Tanya soal pendaftaran, kartu QR, jadwal, atau layanan klinik — sekaligus
                    bisa minta dibuatkan janji temu.
                </p>
            </div>

            <form method="POST" action="{{ route('pasien.chat.baru') }}">
                @csrf
                <button type="submit"
                        class="shrink-0 rounded-[9px] border border-line bg-surface px-3 py-2 text-[13px] font-semibold text-ink hover:bg-slate-50">
                    Percakapan baru
                </button>
            </form>
        </div>

        <div class="mt-4 rounded-xl border border-amber-soft bg-amber-soft px-4 py-3 text-sm text-amber-ink">
            Informasi dari chatbot <strong>bukan pengganti pemeriksaan dokter</strong>. Keluhan yang
            dirasa berat sebaiknya langsung disampaikan ke petugas atau dokter.
        </div>

        <div id="panel-janji" class="mt-4 rounded-xl border border-line bg-surface p-4">
            <h2 class="text-sm font-semibold text-ink">Janji temu aktif</h2>
            @if (! $pasien)
                <p class="mt-1 text-sm text-muted">
                    Belum ada data diri yang disetujui. Lengkapi data diri dulu supaya janji temu bisa dibuat.
                </p>
            @elseif (! $pasien->sudahTerverifikasi())
                <p class="mt-1 text-sm text-muted">
                    Kartu QR <strong>{{ $pasien->no_rm }}</strong> belum diverifikasi petugas. Tunjukkan
                    kartu QR di meja pendaftaran saat datang pertama kali — setelah itu chatbot bisa
                    membuatkan janji.
                </p>
            @elseif ($janji->isEmpty())
                <p class="mt-1 text-sm text-muted">
                    Belum ada janji. Contoh: "saya mau daftar ke dokter gigi besok pagi".
                </p>
            @else
                <ul class="mt-2 space-y-1 text-sm text-ink">
                    @foreach ($janji as $baris)
                        <li>
                            {{ $baris->doctor?->user?->name }} &middot; {{ $baris->poli?->nama }} &middot;
                            {{ $baris->tgl_kunjungan?->format('d M Y') }} {{ substr((string) $baris->jam, 0, 5) }}
                            <span class="text-muted">({{ $baris->status }})</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="mt-4 rounded-xl border border-line bg-surface">
            <div id="kotak-chat" class="max-h-96 space-y-3 overflow-x-hidden overflow-y-auto p-4">
                @forelse ($pesan as $baris)
                    @if ($baris->pengirim === 'pengguna')
                        <div class="flex justify-end">
                            <div class="min-w-0 max-w-[85%] break-words whitespace-pre-wrap rounded-xl bg-primary px-3 py-2 text-sm text-white">
                                {{ $baris->isi }}
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start">
                            <div class="min-w-0 max-w-[85%] break-words whitespace-pre-wrap rounded-xl bg-canvas px-3 py-2 text-sm text-ink">
                                {{ $baris->isi }}
                            </div>
                        </div>
                    @endif
                @empty
                    <p id="pesan-kosong" class="text-sm text-muted">Belum ada percakapan. Tulis pertanyaanmu di bawah.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('pasien.chat.kirim') }}" id="form-chat"
                  data-alir="{{ config('services.llm.chat_stream') ? route('pasien.chat.alir') : '' }}"
                  class="flex items-center gap-2 border-t border-line-soft p-3">
                @csrf
                <div class="flex-1">
                    <label for="pesan" class="sr-only">Pertanyaan</label>
                    {{-- Textarea satu baris yang tumbuh ke bawah saat teks panjang, supaya
                         tulisannya turun ke baris baru, bukan melebar ke samping. --}}
                    <textarea id="pesan" name="pesan" rows="1" maxlength="500" required
                              class="block max-h-32 w-full resize-none overflow-y-auto rounded-lg border border-line px-3 py-2 text-sm leading-6"
                              placeholder="cth. apa syarat daftar berobat?">{{ old('pesan') }}</textarea>
                    @error('pesan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="rounded-lg bg-primary px-4 py-2 text-sm text-white hover:bg-primary-hover">
                    Kirim
                </button>
            </form>
        </div>

        <p class="mt-3 text-xs text-faint">
            Percakapan ini hanya berlaku untuk sesi login ini — keluar atau tekan
            <strong>Percakapan baru</strong> untuk mulai dari layar kosong. Riwayatnya tetap
            tersimpan sebagai riwayat chatbot, bukan rekam medis.
        </p>
    </div>

    <script>
        // Kirim pertanyaan lewat fetch + baca balasan mengalir (SSE) supaya:
        // 1) jawaban muncul bertahap (tidak menunggu selesai),
        // 2) penanda "sedang menyusun" tampil DI DALAM kotak chat, bukan spinner tab,
        // 3) halaman tidak dimuat ulang sehingga posisi scroll tidak melompat.
        (() => {
            const form = document.getElementById('form-chat');
            const input = document.getElementById('pesan');
            const kotak = document.getElementById('kotak-chat');

            if (!form || !input || !kotak || !window.fetch || !window.TextDecoder) {
                return; // browser lama: pakai jalur form biasa
            }

            // Kotak pesan tumbuh KE BAWAH saat teks panjang (bukan melebar ke samping).
            const sesuaikanTinggi = () => {
                input.style.height = 'auto';
                input.style.height = Math.min(input.scrollHeight, 128) + 'px';
            };

            input.addEventListener('input', sesuaikanTinggi);
            sesuaikanTinggi();

            const labelAlat = {
                daftar_dokter: 'mencari dokter yang praktik',
                cek_jadwal: 'mengecek jam kosong',
                buat_janji: 'membuat janji',
                cek_janji: 'membaca janji aktif',
                batal_janji: 'membatalkan janji',
            };

            const gelembung = (pengirim, teks) => {
                const baris = document.createElement('div');
                baris.className = pengirim === 'pengguna' ? 'flex justify-end' : 'flex justify-start';

                const isi = document.createElement('div');
                isi.className = pengirim === 'pengguna'
                    ? 'min-w-0 max-w-[85%] break-words whitespace-pre-wrap rounded-xl bg-primary px-3 py-2 text-sm text-white'
                    : 'min-w-0 max-w-[85%] break-words whitespace-pre-wrap rounded-xl bg-canvas px-3 py-2 text-sm text-ink';
                isi.textContent = teks;

                baris.appendChild(isi);
                kotak.appendChild(baris);

                return isi;
            };

            const hampirDiBawah = () => kotak.scrollHeight - kotak.scrollTop - kotak.clientHeight < 80;
            const keBawah = () => { kotak.scrollTop = kotak.scrollHeight; };

            const segarkanJanji = async (intent) => {
                if (intent !== 'buat_janji' && intent !== 'batal_janji') return;

                const panel = document.getElementById('panel-janji');
                if (!panel) return;

                const gulir = window.scrollY;
                const halaman = await fetch(window.location.href, { headers: { 'Accept': 'text/html' } });
                const dokumen = new DOMParser().parseFromString(await halaman.text(), 'text/html');
                const baru = dokumen.getElementById('panel-janji');

                if (baru) {
                    panel.innerHTML = baru.innerHTML;
                    window.scrollTo(0, gulir); // jangan melompat setelah panel diganti
                }
            };

            // Enter langsung mengirim; tombol "Kirim" tetap ada untuk yang suka klik.
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    form.requestSubmit();
                }
            });

            form.addEventListener('submit', async (e) => {
                const pesan = input.value.trim();

                if (pesan === '') return;

                e.preventDefault();

                document.getElementById('pesan-kosong')?.remove();

                const dekatBawah = hampirDiBawah();

                gelembung('pengguna', pesan);
                input.value = '';
                sesuaikanTinggi();

                const tunggu = gelembung('bot', '');
                tunggu.innerHTML = '<span class="inline-flex items-center gap-1.5 text-muted">'
                    + '<span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400"></span>'
                    + '<span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay:.15s"></span>'
                    + '<span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay:.3s"></span>'
                    + '<span class="text-xs">menyusun jawaban…</span></span>';

                if (dekatBawah) keBawah();
                input.focus();

                try {
                    const token = form.querySelector('input[name="_token"]').value;

                    if (form.dataset.alir === '') {
                        // === Mode bawaan: satu permintaan JSON ===
                        // Terukur lebih cepat di 9Router lokal (1,5-2,2 dtk) daripada mode
                        // streaming (8-17 dtk), dan tetap tanpa muat ulang halaman.
                        const jawab = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({ pesan }),
                        });

                        if (!jawab.ok) {
                            throw new Error('jawaban tidak tersedia');
                        }

                        const data = await jawab.json();
                        tunggu.textContent = data.jawaban ?? '';

                        if (tunggu.textContent === '') {
                            tunggu.textContent = 'Maaf, jawaban belum bisa disusun. Coba tanyakan lagi.';
                        }

                        await segarkanJanji(data.intent);

                        if (dekatBawah) keBawah();
                        input.focus();

                        return;
                    }

                    // === Mode streaming (opsional: CHAT_STREAM=true) ===
                    const jawab = await fetch(form.dataset.alir, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'text/event-stream',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ pesan }),
                    });

                    if (!jawab.ok || !jawab.body) {
                        throw new Error('jalur streaming tidak tersedia');
                    }

                    const pembaca = jawab.body.getReader();
                    const dekoder = new TextDecoder();
                    let sisa = '';
                    let teks = '';
                    let sudahAdaTeks = false;
                    let intent = null;

                    while (true) {
                        const { value, done } = await pembaca.read();
                        if (done) break;

                        sisa += dekoder.decode(value, { stream: true });

                        const blok = sisa.split('\n\n');
                        sisa = blok.pop() ?? '';

                        for (const satuBlok of blok) {
                            for (const baris of satuBlok.split('\n')) {
                                if (!baris.startsWith('data:')) continue;

                                const muatan = baris.slice(5).trim();
                                if (muatan === '' || muatan === '[DONE]') continue;

                                let peristiwa;
                                try {
                                    peristiwa = JSON.parse(muatan);
                                } catch {
                                    continue;
                                }

                                if (peristiwa.delta) {
                                    if (!sudahAdaTeks) {
                                        tunggu.textContent = '';
                                        sudahAdaTeks = true;
                                    }

                                    teks += peristiwa.delta;
                                    tunggu.textContent = teks;

                                    if (dekatBawah) keBawah();
                                } else if (peristiwa.alat) {
                                    tunggu.textContent = (labelAlat[peristiwa.alat] ?? 'memproses') + '…';
                                } else if (peristiwa.selesai) {
                                    intent = peristiwa.intent;
                                }
                            }
                        }
                    }

                    if (!sudahAdaTeks) {
                        tunggu.textContent = 'Maaf, jawaban belum bisa disusun. Coba tanyakan lagi.';
                    }

                    await segarkanJanji(intent);
                } catch (galat) {
                    // Jalur biasa: halaman dimuat ulang (tetap berfungsi tanpa JS/streaming).
                    tunggu.remove();
                    form.submit();

                    return;
                }

                if (dekatBawah) keBawah();
                input.focus();
            });
        })();
    </script>
@endsection
