@extends('layouts.app')

@section('judul', 'Tanya Chatbot')

@section('isi')
    <div class="mx-auto max-w-2xl">
        <h1 class="text-xl font-semibold text-ink">Tanya chatbot</h1>
        <p class="mt-1 text-sm text-muted">
            Tanya soal pendaftaran, kartu QR, jadwal, atau layanan klinik.
            Pembuatan janji lewat chat menyusul.
        </p>

        <div class="mt-4 rounded-xl border border-amber-soft bg-amber-soft px-4 py-3 text-sm text-amber-ink">
            Informasi dari chatbot <strong>bukan pengganti pemeriksaan dokter</strong>. Keluhan yang
            dirasa berat sebaiknya langsung disampaikan ke petugas atau dokter.
        </div>

        <div class="mt-4 rounded-xl border border-line bg-surface p-4">
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
            <div class="max-h-96 space-y-3 overflow-y-auto p-4">
                @forelse ($pesan as $baris)
                    @if ($baris->pengirim === 'pengguna')
                        <div class="flex justify-end">
                            <div class="max-w-[85%] rounded-xl bg-primary px-3 py-2 text-sm text-white">
                                {{ $baris->isi }}
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start">
                            <div class="max-w-[85%] rounded-xl bg-canvas px-3 py-2 text-sm text-ink">
                                {{ $baris->isi }}
                            </div>
                        </div>
                    @endif
                @empty
                    <p class="text-sm text-muted">Belum ada percakapan. Tulis pertanyaanmu di bawah.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('pasien.chat.kirim') }}"
                  class="flex items-end gap-2 border-t border-line-soft p-3">
                @csrf
                <div class="flex-1">
                    <label for="pesan" class="sr-only">Pertanyaan</label>
                    <textarea id="pesan" name="pesan" rows="2" maxlength="500" required
                              class="w-full rounded-lg border border-line px-3 py-2 text-sm"
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
            Percakapan ini tersimpan sebagai riwayat chatbot, bukan rekam medis.
        </p>
    </div>
@endsection
