@extends('layouts.app')

@section('judul', 'Dashboard Admin')
@section('crumb', '')
@section('main-class', 'max-w-none px-6 xl:px-8 pt-9 pb-14 max-[1080px]:px-4 max-[1080px]:pt-7')

@section('isi')
    @php
        // Satu gradien untuk semua kartu: angka yang membedakan, bukan warna.
        $meta = [
            'Akun staf aktif' => [
                'ikon' => 'users',
                'satuan' => 'akun',
                'ket' => 'Akun staf yang bisa masuk ke sistem',
            ],
            'Dokter' => [
                'ikon' => 'dokter',
                'satuan' => 'orang',
                'ket' => 'Dokter terdaftar di klinik',
            ],
            'Poli' => [
                'ikon' => 'poli',
                'satuan' => 'poli',
                'ket' => 'Poli layanan aktif',
            ],
            'Pasien terdaftar' => [
                'ikon' => 'users',
                'satuan' => 'orang',
                'ket' => 'Total data diri pasien',
            ],
            'Kunjungan hari ini' => [
                'ikon' => 'kalender',
                'satuan' => 'kunjungan',
                'ket' => 'Kunjungan terjadwal hari ini',
            ],
        ];
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[24px] leading-tight font-bold tracking-tight text-ink">Dashboard admin</h1>
            <p class="mt-1 text-sm text-muted">Ringkasan data klinik saat ini.</p>
        </div>
        <p class="text-[13px] font-medium text-faint">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</p>
    </div>

    {{-- Satu grid rata: 5 kartu sekolom, tidak ada kartu yang ditarik melebar. --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        @foreach ($statistik as $label => $nilai)
            @php
                $item = $meta[$label] ?? [
                    'ikon' => 'users',
                    'ket' => 'Data klinik',
                ];
            @endphp
            <div class="rounded-[14px] p-5 text-white shadow-[0_8px_20px_-10px_rgba(29,78,216,.55)]"
                 style="background-image: linear-gradient(135deg, #1d4ed8, #3b82f6)">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[15.5px] font-semibold capitalize text-white/95">{{ $label }}</p>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/20">
                        @switch($item['ikon'])
                            @case('dokter')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6a3 3 0 0 1-6 0V8"/></svg>
                                @break
                            @case('poli')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-4h6v4"/></svg>
                                @break
                            @case('kalender')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                @break
                            @default
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        @endswitch
                    </span>
                </div>

                <p class="mt-7 flex items-baseline gap-2">
                    <span class="text-[64px] leading-[.85] font-bold tracking-[-.03em]">{{ $nilai }}</span>
                    <span class="text-[14px] font-medium text-white/80">{{ $item['satuan'] ?? 'total' }}</span>
                </p>
                <p class="mt-3 text-[12.5px] leading-snug text-white/85">{{ $item['ket'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.04)]">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-[17px] font-semibold tracking-tight text-ink">Analitik kunjungan</h2>
                <p class="mt-0.5 text-[13px] text-muted">Rekap bulan {{ $analitik['bulanLabel'] }}, berdasar tanggal kunjungan.</p>
            </div>
            <form method="GET" action="{{ route('dashboard') }}">
                <label for="bulan" class="sr-only">Pilih bulan</label>
                <select id="bulan" name="bulan" onchange="this.form.submit()"
                        class="h-[34px] rounded-[8px] border border-line bg-white px-3 text-[13px] font-medium text-ink focus:border-primary focus:outline-none">
                    @foreach ($analitik['opsiBulan'] as $opsi)
                        <option value="{{ $opsi['nilai'] }}" @selected($opsi['nilai'] === $analitik['bulanNilai'])>{{ $opsi['label'] }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-[10px] bg-slate-50 px-4 py-3">
                <dt class="text-[12.5px] text-muted">Total kunjungan</dt>
                <dd class="mt-0.5 text-[22px] font-bold tracking-tight text-ink">{{ $analitik['rekap']['total'] }}</dd>
            </div>
            <div class="rounded-[10px] bg-slate-50 px-4 py-3">
                <dt class="text-[12.5px] text-muted">Rata-rata per hari</dt>
                <dd class="mt-0.5 text-[22px] font-bold tracking-tight text-ink">{{ $analitik['rekap']['rata'] }}</dd>
            </div>
            <div class="rounded-[10px] bg-slate-50 px-4 py-3">
                <dt class="text-[12.5px] text-muted">Hari tersibuk</dt>
                <dd class="mt-0.5 text-[15px] font-semibold text-ink">
                    {{ $analitik['rekap']['tersibuk'] ?? 'Belum ada kunjungan' }}
                    @if ($analitik['rekap']['tersibuk'])
                        <span class="font-normal text-muted">({{ $analitik['rekap']['kunjunganTersibuk'] }} kunjungan)</span>
                    @endif
                </dd>
            </div>
        </dl>

        <div class="mt-5 grid grid-cols-1 gap-6 xl:grid-cols-5">
            <div class="xl:col-span-3">
                <p class="text-[13px] font-semibold text-ink">Kunjungan per hari</p>
                <div class="relative mt-2 h-56"><canvas id="grafik-kunjungan"></canvas></div>
            </div>
            <div class="xl:col-span-2">
                <p class="text-[13px] font-semibold text-ink">Poli tersibuk</p>
                @if ($analitik['rekap']['poliTersibuk'])
                    <p class="mt-1 text-[12.5px] text-muted">{{ $analitik['rekap']['poliTersibuk'] }} memimpin dengan {{ $analitik['rekap']['kunjunganPoliTersibuk'] }} kunjungan.</p>
                @endif
                <div class="relative mt-2 h-56"><canvas id="grafik-poli"></canvas></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            const hari = @json($analitik['labelHari']);
            const kunjungan = @json($analitik['nilaiHari']);
            const poli = @json($analitik['labelPoli']);
            const kunjunganPoli = @json($analitik['nilaiPoli']);

            new Chart(document.getElementById('grafik-kunjungan'), {
                type: 'line',
                data: { labels: hari, datasets: [{ label: 'Kunjungan', data: kunjungan, borderColor: '#1d4ed8', backgroundColor: 'rgba(29,78,216,.12)', fill: true, tension: .35, pointRadius: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { maxTicksLimit: 8 } } } },
            });

            new Chart(document.getElementById('grafik-poli'), {
                type: 'bar',
                data: { labels: poli.length ? poli : ['Belum ada data'], datasets: [{ data: poli.length ? kunjunganPoli : [0], backgroundColor: '#1d4ed8', borderRadius: 6 }] },
                options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
            });
        </script>
    @endpush

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
