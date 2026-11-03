@extends('layouts.app')

@section('judul', 'Dashboard Admin')
@section('crumb', '')
@section('main-class', 'max-w-none px-6 xl:px-8 pt-9 pb-14 max-[1080px]:px-4 max-[1080px]:pt-7')

@section('isi')
    @php
        $meta = [
            'Akun staf aktif' => [
                'ikon' => 'users',
                'warna' => 'bg-indigo-soft text-indigo-ink',
                'ket' => 'Akun staf yang bisa masuk ke sistem',
            ],
            'Dokter' => [
                'ikon' => 'dokter',
                'warna' => 'bg-accent-soft text-accent',
                'ket' => 'Dokter terdaftar di klinik',
            ],
            'Poli' => [
                'ikon' => 'poli',
                'warna' => 'bg-green-soft text-green-ink',
                'ket' => 'Poli layanan aktif',
            ],
            'Pasien terdaftar' => [
                'ikon' => 'users',
                'warna' => 'bg-primary-soft text-primary',
                'ket' => 'Total data diri pasien',
            ],
            'Obat' => [
                'ikon' => 'obat',
                'warna' => 'bg-indigo-soft text-indigo-ink',
                'ket' => 'Obat pada data master',
            ],
            'Obat stok menipis' => [
                'ikon' => 'peringatan',
                'warna' => 'bg-amber-soft text-amber-ink',
                'ket' => 'Perlu ditambah stoknya',
            ],
            'Kunjungan hari ini' => [
                'ikon' => 'kalender',
                'warna' => 'bg-green-soft text-green-ink',
                'ket' => 'Kunjungan terjadwal hari ini',
            ],
        ];

        $ringkasan = array_intersect_key($statistik, array_flip([
            'Akun staf aktif', 'Dokter', 'Pasien terdaftar', 'Kunjungan hari ini',
        ]));
        $dataMaster = array_intersect_key($statistik, array_flip([
            'Poli', 'Obat', 'Obat stok menipis',
        ]));
        $grupStatistik = [
            ['judul' => 'Ringkasan', 'items' => $ringkasan, 'ratakan' => false],
            ['judul' => 'Data master', 'items' => $dataMaster, 'ratakan' => true],
        ];
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[24px] leading-tight font-bold tracking-tight text-ink">Dashboard admin</h1>
            <p class="mt-1 text-sm text-muted">Ringkasan data klinik saat ini.</p>
        </div>
        <p class="text-[13px] font-medium text-faint">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</p>
    </div>

    @foreach ($grupStatistik as $grup)
        <div class="mb-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 {{ $grup['judul'] === 'Ringkasan' ? 'xl:grid-cols-4' : 'xl:grid-cols-3' }}">
                @foreach ($grup['items'] as $label => $nilai)
                    @php
                        $item = $meta[$label] ?? [
                            'ikon' => 'users',
                            'warna' => 'bg-accent-soft text-accent',
                            'ket' => 'Data klinik',
                        ];
                        $span = $grup['ratakan'] && $loop->last ? 'sm:col-span-2 xl:col-span-1' : '';
                    @endphp
                    <div class="rounded-[14px] border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(16,24,40,.04)] {{ $span }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-[19px] font-semibold text-ink capitalize">{{ $label }}</p>
                                <p class="mt-3 text-[40px] leading-none font-bold tracking-tight {{ $label === 'Obat stok menipis' && $nilai > 0 ? 'text-amber-ink' : 'text-ink' }}">
                                    {{ $nilai }}
                                </p>
                            </div>
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $item['warna'] }}">
                                @switch($item['ikon'])
                                    @case('dokter')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6a3 3 0 0 1-6 0V8"/></svg>
                                        @break
                                    @case('poli')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-4h6v4"/></svg>
                                        @break
                                    @case('obat')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.8 3h10.4a2 2 0 0 0 1.8-3l-5-9V3"/></svg>
                                        @break
                                    @case('peringatan')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                                        @break
                                    @case('kalender')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                        @break
                                    @default
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                @endswitch
                            </span>
                        </div>
                        <p class="mt-4 text-[14px] leading-snug text-muted">{{ $item['ket'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.04)]">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-[17px] font-semibold tracking-tight text-ink">Yang bisa dikerjakan sekarang</h2>
                <p class="mt-0.5 text-[13px] text-muted">Modul admin yang sedang disiapkan</p>
            </div>
            <span class="rounded-full bg-accent-soft px-2.5 py-1 text-[11.5px] font-semibold text-primary">2 modul</span>
        </div>

        <ul class="mt-5 divide-y divide-line-soft">
            <li class="flex gap-3.5 py-3.5 first:pt-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-soft text-indigo-ink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-medium text-ink">Data master</p>
                        <span class="rounded-full bg-amber-soft px-2.5 py-1 text-[10.5px] font-semibold text-amber-ink">Menyusul</span>
                    </div>
                    <p class="mt-0.5 text-[12.5px] text-muted">Poli, dokter, jadwal praktik, tarif, ICD-10, dan obat.</p>
                </div>
            </li>
            <li class="flex gap-3.5 py-3.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-green-soft text-green-ink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-medium text-ink">Laporan &amp; log aktivitas</p>
                        <span class="rounded-full bg-amber-soft px-2.5 py-1 text-[10.5px] font-semibold text-amber-ink">Menyusul</span>
                    </div>
                    <p class="mt-0.5 text-[12.5px] text-muted">Ekspor PDF/Excel dan penelusuran aktivitas pengguna.</p>
                </div>
            </li>
        </ul>

        <p class="mt-4 text-[12px] text-faint">Daftar fitur lengkap per role: docs/FEATURES.md</p>
    </div>
@endsection
