@extends('layouts.app')

@section('judul', 'Data Pasien Terdaftar')
@section('crumb', 'Detail Pasien Walk-in')

@section('isi')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink">Pasien Berhasil Didaftarkan</h1>
                <p class="text-sm text-muted">Data pasien dan kartu QR unik telah tersimpan di sistem.</p>
            </div>
            <a href="{{ route('petugas.pasien.create') }}" class="text-sm text-primary hover:underline">
                + Pasien Baru Lagi
            </a>
        </div>

        <div class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <div class="flex flex-col items-center sm:flex-row sm:items-start gap-6 border-b border-line pb-6">
                <div class="flex flex-col items-center rounded-xl border border-line bg-slate-50 p-4">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                        ->size(170)
                        ->margin(1)
                        ->generate($pasien->qr_token) !!}
                    <span class="mt-2 font-mono text-xs font-semibold text-muted">{{ $pasien->no_rm }}</span>
                </div>

                <div class="flex-1 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $pasien->sudahTerverifikasi() ? 'bg-green-soft text-green-ink' : 'bg-amber-soft text-amber-ink' }}">
                            {{ $pasien->sudahTerverifikasi() ? 'Terverifikasi (KTP Sesuai)' : 'Belum Verifikasi' }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                            Walk-in
                        </span>
                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-ink">{{ $pasien->nama_lengkap }}</h2>
                        <p class="font-mono text-sm text-primary font-semibold">No. RM: {{ $pasien->no_rm }}</p>
                    </div>

                    <dl class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <dt class="text-muted">Tanggal Lahir:</dt>
                            <dd class="font-medium text-ink">{{ $pasien->tgl_lahir?->format('d-m-Y') }} ({{ $pasien->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }})</dd>
                        </div>
                        <div>
                            <dt class="text-muted">No. WhatsApp:</dt>
                            <dd class="font-medium text-ink">{{ $pasien->no_wa }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted">Golongan Darah:</dt>
                            <dd class="font-medium text-ink">{{ $pasien->golongan_darah ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted">Riwayat Alergi:</dt>
                            <dd class="font-medium text-ink">{{ $pasien->alergi ?: 'Tidak ada' }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-muted">Alamat:</dt>
                            <dd class="font-medium text-ink">{{ $pasien->alamat ?: '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    @if (auth()->user()->punyaRole('admin'))
                        <a href="{{ route('admin.pasien.show', $pasien) }}"
                           class="inline-flex h-[38px] items-center gap-2 rounded-[9px] border border-line bg-surface px-4 text-xs font-semibold text-primary hover:bg-slate-50">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/></svg>
                            Lihat Detail Data Diri
                        </a>
                    @endif
                    <a href="{{ route('petugas.pasien.kartu', $pasien) }}" target="_blank"
                       class="inline-flex h-[38px] items-center gap-2 rounded-[9px] border border-line bg-surface px-4 text-xs font-semibold text-ink hover:bg-slate-50">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Cetak Kartu Pasien
                    </a>

                    @if ($waLink)
                        <a href="{{ $waLink }}" target="_blank"
                           class="inline-flex h-[38px] items-center gap-2 rounded-[9px] bg-emerald-600 px-4 text-xs font-semibold text-white hover:bg-emerald-700">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                            Kirim ke WhatsApp
                        </a>
                    @endif
                </div>

                <a href="{{ route('petugas.scan.hasil', $pasien) }}"
                   class="inline-flex h-[38px] items-center gap-2 rounded-[9px] bg-primary px-4 text-xs font-semibold text-white hover:bg-primary-hover">
                    Lanjut Check-in Antrean &rarr;
                </a>
            </div>
        </div>
    </div>
@endsection

