@extends('layouts.app')

@section('judul', 'Kartu QR')

@section('isi')
    <div class="mx-auto max-w-xl">
        <h1 class="mb-1 text-xl font-semibold">Kartu QR kamu</h1>
        <p class="mb-6 text-sm text-slate-500">
            Tunjukkan kode ini ke petugas. Setiap pemindaian tercatat di sistem.
            @if ($jumlahProfil > 1)
                Kartu ini memakai data diri <strong>{{ $pasien->nama_lengkap }}</strong> —
                <a href="{{ route('pasien.profil') }}" class="text-teal-700 hover:underline">ganti data diri</a>
                kalau yang mau dipakai orang lain.
            @endif
        </p>

        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <dl class="mb-4 space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Nama</dt>
                    <dd class="font-medium">
                        {{ $pasien->nama_lengkap }}
                        @if ($pasien->hubungan_keluarga !== 'diri sendiri')
                            <span class="font-normal text-slate-500">({{ $pasien->hubungan_keluarga }})</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Nomor rekam medis</dt>
                    <dd class="font-medium">{{ $pasien->no_rm }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Status</dt>
                    <dd class="font-medium">
                        {{ $pasien->sudahTerverifikasi() ? 'terverifikasi' : 'belum terverifikasi' }}
                    </dd>
                </div>
            </dl>

            <div class="mb-4 flex justify-center rounded-md bg-white p-3">
                {{-- Isi QR = token acak UUID. Data identitas dan data medis tidak boleh masuk QR. --}}
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(220)
                    ->margin(1)
                    ->generate($pasien->qr_token) !!}
            </div>

            <p class="mb-2 text-xs text-slate-500">Token QR (isinya token acak — bukan data identitas, bukan data medis):</p>
            <p class="break-all rounded-md bg-slate-50 px-3 py-2 font-mono text-xs">{{ $pasien->qr_token }}</p>

            <button type="button" onclick="window.print()"
                    class="mt-4 rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">
                Cetak kartu
            </button>

            <p class="mt-3 text-xs text-slate-400">
                QR dibuat oleh paket <span class="font-mono">simplesoftwareio/simple-qrcode</span> (disetujui Jian).
                Petugas memindainya di halaman <span class="font-mono">/petugas/scan</span>.
            </p>
        </div>

        @if (! $pasien->sudahTerverifikasi())
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Kartu ini akan diverifikasi petugas saat kamu datang pertama kali (pencocokan KTP).
            </div>
        @endif
    </div>
@endsection
