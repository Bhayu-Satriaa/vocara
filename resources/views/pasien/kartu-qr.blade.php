@extends('layouts.app')

@section('judul', 'Kartu QR')
@section('crumb', 'Kartu QR Pasien')

@section('isi')
    <div class="mx-auto max-w-xl">
        <h1 class="mb-1 text-[30px] font-bold tracking-tight leading-tight max-[820px]:text-2xl text-ink">Kartu QR kamu</h1>
        <p class="mb-6 text-sm text-muted">
            Tunjukkan kode ini ke petugas. Setiap pemindaian tercatat di sistem.
            @if ($jumlahProfil > 1)
                Kartu ini memakai data diri <strong>{{ $pasien->nama_lengkap }}</strong> —
                <a href="{{ route('pasien.profil') }}" class="text-primary hover:underline">ganti data diri</a>
                kalau yang mau dipakai orang lain.
            @endif
        </p>

        <div class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <dl class="mb-5 space-y-2.5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Nama</dt>
                    <dd class="font-medium text-ink">
                        {{ $pasien->nama_lengkap }}
                        @if ($pasien->hubungan_keluarga !== 'diri sendiri')
                            <span class="font-normal text-muted">({{ $pasien->hubungan_keluarga }})</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Nomor rekam medis</dt>
                    <dd class="font-mono font-semibold text-ink">{{ $pasien->no_rm }}</dd>
                </div>
                <div class="flex justify-between gap-4 items-center">
                    <dt class="text-muted">Status</dt>
                    <dd>
                        @if ($pasien->sudahTerverifikasi())
                            <span class="rounded-full bg-green-soft px-2.5 py-1 text-[11.5px] font-semibold text-green-ink">
                                Data Diri Terverifikasi
                            </span>
                        @else
                            <span class="rounded-full bg-accent-soft px-2.5 py-1 text-[11.5px] font-semibold text-accent">
                                Terdaftar
                            </span>
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="mb-5 flex justify-center rounded-[10px] border border-line-soft bg-slate-50 p-4">
                {{-- Isi QR = token acak UUID. Data identitas dan data medis tidak boleh masuk QR. --}}
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(220)
                    ->margin(1)
                    ->generate($pasien->qr_token) !!}
            </div>

            <p class="mb-1.5 text-xs text-muted">Token QR (isinya token acak — bukan data identitas, bukan data medis):</p>
            <p class="break-all rounded-[10px] border border-line-soft bg-slate-50 px-3.5 py-2 font-mono text-xs text-ink">{{ $pasien->qr_token }}</p>

            <button type="button" onclick="window.print()"
                    class="mt-5 h-[38px] cursor-pointer rounded-[9px] border border-line bg-surface px-3.5 text-[13.5px] font-semibold text-ink hover:bg-slate-50">
                Cetak kartu
            </button>

            <p class="mt-4 text-xs text-faint">
                QR dibuat oleh paket <span class="font-mono">simplesoftwareio/simple-qrcode</span> (disetujui Jian).
                Petugas memindainya di halaman <span class="font-mono">/petugas/scan</span>.
            </p>
        </div>

        @if (! $pasien->sudahTerverifikasi())
            <div class="mt-4 rounded-[10px] border border-line-soft bg-slate-50 px-4 py-3 text-[13px] text-muted">
                Data diri kamu sudah <strong class="text-ink">terdaftar</strong> di sistem. Verifikasi Data Diri akan
                dilakukan petugas loket saat kunjungan pertama dengan mencocokkan data fisik KTP.
            </div>
        @endif
    </div>
@endsection
