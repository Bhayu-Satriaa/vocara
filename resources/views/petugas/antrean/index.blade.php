@extends('layouts.app')

@section('judul', 'Kelola Antrean Pasien')
@section('crumb', 'Antrean hari ini')

@section('isi')
    <div class="space-y-6">
        {{-- Header Halaman --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-[28px] font-bold tracking-tight text-ink max-[820px]:text-2xl">
                    Kelola Antrean Pasien
                </h1>
                <p class="text-sm text-muted">
                    Pantau alur pelayanan klinik hari ini, panggil pasien dengan suara (TTS), dan konfirmasi booking online.
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('petugas.scan') }}"
                   class="inline-flex h-9 items-center gap-2 rounded-xl bg-primary px-3.5 text-xs font-semibold text-white transition-all hover:bg-primary-hover shadow-xs no-underline">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M3 12h18"/></svg>
                    Scan QR Check-in
                </a>
            </div>
        </div>

        {{-- Kartu Ringkasan Statistik --}}
        <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-5">
            <div class="rounded-2xl border border-line bg-surface p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-muted">Total Antrean</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold tracking-tight text-ink">{{ $stats['total'] }}</p>
                <span class="text-[11px] text-faint">Kunjungan hari ini</span>
            </div>

            <div class="rounded-2xl border border-amber-200/80 bg-amber-50/50 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-amber-900">Menunggu</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold tracking-tight text-amber-950">{{ $stats['menunggu'] }}</p>
                <span class="text-[11px] text-amber-700">Belum masuk ruang</span>
            </div>

            <div class="rounded-2xl border border-blue-200/80 bg-blue-50/50 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-blue-900">Diperiksa</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold tracking-tight text-blue-950">{{ $stats['diperiksa'] }}</p>
                <span class="text-[11px] text-blue-700">Di ruang dokter</span>
            </div>

            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/50 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-emerald-900">Selesai</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-950">{{ $stats['selesai'] }}</p>
                <span class="text-[11px] text-emerald-700">Pelayanan selesai</span>
            </div>

            <div class="rounded-2xl border border-purple-200/80 bg-purple-50/50 p-4 shadow-xs col-span-2 sm:col-span-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-purple-900">Booking Pending</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-100 text-purple-700">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold tracking-tight text-purple-950">{{ $stats['booking_pending'] }}</p>
                <span class="text-[11px] text-purple-700">Perlu konfirmasi</span>
            </div>
        </div>

        {{-- Navigasi Tab Antrean vs Booking Online --}}
        <div class="flex border-b border-line">
            <button type="button" id="tab-antrean-btn"
                    class="tab-btn flex items-center gap-2 border-b-2 border-primary pb-3 px-3 text-sm font-semibold text-primary transition-colors">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                Antrean Hari Ini
                <span class="rounded-full bg-primary-soft px-2 py-0.5 text-xs text-primary font-bold">
                    {{ $antreans->total() }}
                </span>
            </button>
            <button type="button" id="tab-booking-btn"
                    class="tab-btn flex items-center gap-2 border-b-2 border-transparent pb-3 px-3 text-sm font-medium text-muted transition-colors hover:text-ink">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Booking Online
                @if ($bookingList->isNotEmpty())
                    <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-800 font-bold">
                        {{ $bookingList->count() }}
                    </span>
                @endif
            </button>
        </div>

        {{-- 1. SECTION ANTREAN HARI INI --}}
        <div id="section-antrean" class="space-y-4">
            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('petugas.antrean.index') }}"
                  class="flex flex-wrap items-center gap-2.5 rounded-2xl border border-line bg-surface p-3.5 shadow-xs">
                <div class="relative min-w-[200px] flex-1">
                    <input type="text" name="q" value="{{ $searchQuery }}"
                           placeholder="Cari nama pasien, no RM, nomor..."
                           class="h-9 w-full rounded-xl border border-line bg-canvas pl-9 pr-3 text-xs text-ink placeholder:text-faint focus:border-primary focus:outline-none">
                    <svg class="absolute left-3 top-2.5 text-muted" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>

                <div class="w-full sm:w-auto">
                    <select name="poli_id" onchange="this.form.submit()"
                            class="h-9 w-full rounded-xl border border-line bg-canvas px-3 text-xs text-ink focus:border-primary focus:outline-none">
                        <option value="">Semua Poli</option>
                        @foreach ($polis as $poli)
                            <option value="{{ $poli->id }}" @selected($filterPoliId == $poli->id)>{{ $poli->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-auto">
                    <select name="status" onchange="this.form.submit()"
                            class="h-9 w-full rounded-xl border border-line bg-canvas px-3 text-xs text-ink focus:border-primary focus:outline-none">
                        <option value="aktif" @selected($filterStatus === 'aktif')>Status Aktif (Menunggu/Diperiksa)</option>
                        <option value="semua" @selected($filterStatus === 'semua')>Semua Status</option>
                        <option value="menunggu" @selected($filterStatus === 'menunggu')>Menunggu</option>
                        <option value="diperiksa" @selected($filterStatus === 'diperiksa')>Diperiksa</option>
                        <option value="farmasi" @selected($filterStatus === 'farmasi')>Farmasi</option>
                        <option value="pembayaran" @selected($filterStatus === 'pembayaran')>Pembayaran</option>
                        <option value="selesai" @selected($filterStatus === 'selesai')>Selesai</option>
                        <option value="batal" @selected($filterStatus === 'batal')>Batal</option>
                    </select>
                </div>

                <div class="w-full sm:w-auto">
                    <input type="date" name="tanggal" value="{{ $filterTanggal }}" onchange="this.form.submit()"
                           class="h-9 w-full rounded-xl border border-line bg-canvas px-3 text-xs text-ink focus:border-primary focus:outline-none">
                </div>

                <button type="submit"
                        class="h-9 rounded-xl bg-slate-100 px-3.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition-colors">
                    Filter
                </button>

                @if ($searchQuery || $filterPoliId || $filterStatus !== 'aktif' || $filterTanggal !== today()->toDateString())
                    <a href="{{ route('petugas.antrean.index') }}"
                       class="text-xs text-muted hover:text-red-600 no-underline px-1">
                        Reset
                    </a>
                @endif
            </form>

            {{-- Tabel Antrean --}}
            <div class="overflow-hidden rounded-2xl border border-line bg-surface shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-line bg-canvas text-muted">
                            <tr>
                                <th class="py-3 px-4 font-semibold w-16 text-center">No.</th>
                                <th class="py-3 px-4 font-semibold">Pasien</th>
                                <th class="py-3 px-4 font-semibold">Tujuan Layanan</th>
                                <th class="py-3 px-4 font-semibold text-center">Check-in</th>
                                <th class="py-3 px-4 font-semibold text-center">Status</th>
                                <th class="py-3 px-4 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line-soft">
                            @forelse ($antreans as $item)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    {{-- Nomor Antrean --}}
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-primary-soft font-mono text-sm font-bold text-primary">
                                            #{{ $item->no_antrian ?? '-' }}
                                        </span>
                                    </td>

                                    {{-- Data Pasien --}}
                                    <td class="py-3.5 px-4">
                                        <p class="font-semibold text-ink text-[13px]">{{ $item->patient->nama_lengkap }}</p>
                                        <p class="text-faint font-mono mt-0.5">RM: {{ $item->patient->no_rm }} &bull; {{ $item->patient->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</p>
                                        @if ($item->keluhan_awal)
                                            <p class="text-muted mt-1 italic text-[11px] line-clamp-1">"{{ $item->keluhan_awal }}"</p>
                                        @endif
                                    </td>

                                    {{-- Poli & Dokter --}}
                                    <td class="py-3.5 px-4">
                                        <p class="font-medium text-ink">{{ $item->poli->nama ?? 'Poli Umum' }}</p>
                                        <p class="text-muted text-[11.5px]">{{ $item->doctor?->user?->name ?? 'Dokter Bertugas' }}</p>
                                    </td>

                                    {{-- Jam Check-in --}}
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="font-mono text-slate-700 font-medium">
                                            {{ $item->checkin_at ? $item->checkin_at->format('H:i') : ($item->jam ? substr($item->jam, 0, 5) : '-') }}
                                        </span>
                                        <span class="block text-[10px] text-faint">
                                            {{ $item->sumber === 'walk_in' ? 'Walk-in' : 'Online' }}
                                        </span>
                                    </td>

                                    {{-- Status Badge --}}
                                    <td class="py-3.5 px-4 text-center">
                                        @php
                                            $statusClasses = [
                                                'booking' => 'bg-purple-50 text-purple-700 border-purple-200',
                                                'menunggu' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'diperiksa' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                'farmasi' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                                'pembayaran' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                                'selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'batal' => 'bg-red-50 text-red-700 border-red-200',
                                            ];
                                            $badgeClass = $statusClasses[$item->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                        @endphp
                                        <span class="inline-flex items-center rounded-lg border px-2.5 py-1 text-[11px] font-semibold capitalize {{ $badgeClass }}">
                                            {{ $item->status }}
                                        </span>
                                    </td>

                                    {{-- Tombol Aksi --}}
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            {{-- Tombol Panggil Suara --}}
                                            @if (!in_array($item->status, ['selesai', 'batal']))
                                                <button type="button"
                                                        data-visit-id="{{ $item->id }}"
                                                        data-nomor="{{ $item->no_antrian }}"
                                                        data-nama="{{ $item->patient->nama_lengkap }}"
                                                        data-poli="{{ $item->poli?->nama ?? 'ruang periksa' }}"
                                                        class="btn-panggil inline-flex h-8 items-center gap-1 rounded-lg bg-emerald-600 px-2.5 text-[11.5px] font-semibold text-white shadow-xs hover:bg-emerald-700 transition-colors">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>
                                                    Panggil
                                                </button>

                                                {{-- Tombol Batal --}}
                                                <button type="button"
                                                        onclick="bukaModalBatal('{{ $item->id }}', '{{ $item->no_antrian }}', '{{ $item->patient->nama_lengkap }}')"
                                                        title="Batalkan Antrean (Pasien Tidak Hadir / Batal)"
                                                        class="inline-flex h-8 items-center gap-1 rounded-lg border border-line px-2 text-[11px] font-medium text-muted hover:border-red-300 hover:bg-red-50 hover:text-red-700 transition-colors">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                    Batal
                                                </button>
                                            @else
                                                <span class="text-[11px] text-faint italic">Tidak ada aksi</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-muted">
                                        <svg class="mx-auto mb-2 text-faint" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>
                                        <p class="font-medium text-sm text-ink">Tidak ada antrean yang sesuai filter</p>
                                        <p class="text-xs text-faint mt-0.5">Pasien yang telah check-in hari ini akan otomatis muncul di sini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($antreans->hasPages())
                    <div class="border-t border-line px-4 py-3 bg-canvas">
                        {{ $antreans->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- 2. SECTION BOOKING ONLINE MENUNGGU KONFIRMASI --}}
        <div id="section-booking" class="hidden space-y-4">
            <div class="overflow-hidden rounded-2xl border border-line bg-surface shadow-xs">
                <div class="p-4 border-b border-line bg-canvas flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-ink">Janji Temu Online Pasien</h2>
                        <p class="text-xs text-muted">Daftar booking melalui portal pasien atau chatbot yang belum check-in.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-line bg-canvas text-muted">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Tgl / Jam Booking</th>
                                <th class="py-3 px-4 font-semibold">Pasien</th>
                                <th class="py-3 px-4 font-semibold">Poli & Dokter</th>
                                <th class="py-3 px-4 font-semibold">Keluhan</th>
                                <th class="py-3 px-4 font-semibold text-right">Aksi Konfirmasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line-soft">
                            @forelse ($bookingList as $book)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <span class="font-semibold text-ink text-[12.5px] block">
                                            {{ $book->tgl_kunjungan->translatedFormat('d M Y') }}
                                        </span>
                                        <span class="font-mono text-muted text-[11px]">
                                            Jam: {{ substr($book->jam, 0, 5) }} WITA
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <p class="font-semibold text-ink text-[13px]">{{ $book->patient->nama_lengkap }}</p>
                                        <p class="text-faint font-mono text-[11px]">RM: {{ $book->patient->no_rm }} &bull; WA: {{ $book->patient->no_wa ?? '-' }}</p>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <p class="font-medium text-ink">{{ $book->poli->nama ?? 'Poli Umum' }}</p>
                                        <p class="text-muted text-[11.5px]">{{ $book->doctor?->user?->name ?? 'Dokter Bertugas' }}</p>
                                    </td>
                                    <td class="py-3.5 px-4 text-muted max-w-xs">
                                        <span class="italic line-clamp-2">{{ $book->keluhan_awal ?? 'Tidak ada catatan keluhan.' }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            {{-- Konfirmasi Verifikasi Kedatangan (Check-in) --}}
                                            <form method="POST" action="{{ route('petugas.antrean.konfirmasi', $book) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-primary px-3 text-xs font-semibold text-white hover:bg-primary-hover shadow-xs transition-colors">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                    Verifikasi Hadir & Check-in
                                                </button>
                                            </form>

                                            {{-- Tolak Booking --}}
                                            <button type="button"
                                                    onclick="bukaModalBatal('{{ $book->id }}', 'Booking {{ $book->patient->nama_lengkap }}', '{{ $book->patient->nama_lengkap }}')"
                                                    class="inline-flex h-8 items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition-colors">
                                                Tolak
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-muted">
                                        <svg class="mx-auto mb-2 text-faint" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
                                        <p class="font-medium text-sm text-ink">Tidak ada booking online yang menunggu konfirmasi</p>
                                        <p class="text-xs text-faint mt-0.5">Semua booking telah diproses atau belum ada pasien yang memesan jadwal.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PEMBATALAN ANTREAN --}}
    <div id="modal-batal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-xl">
            <h3 class="text-base font-bold text-ink" id="modal-batal-title">Batalkan Antrean</h3>
            <p class="mt-1 text-xs text-muted" id="modal-batal-subtitle">Tindakan ini akan membatalkan kunjungan pasien ini dari antrean klinik.</p>

            <form id="form-batal-antrean" method="POST" action="" class="mt-4">
                @csrf
                <label for="alasan-batal" class="block text-xs font-semibold text-ink mb-1">
                    Alasan Pembatalan <span class="text-red-500">*</span>
                </label>
                <textarea id="alasan-batal" name="alasan" required rows="3"
                          placeholder="Cth: Pasien tidak hadir setelah dipanggil berulang kali / Membatalkan secara mandiri"
                          class="w-full rounded-xl border border-line bg-canvas p-2.5 text-xs text-ink focus:border-primary focus:outline-none placeholder:text-faint"></textarea>

                <div class="mt-5 flex justify-end gap-2.5">
                    <button type="button" onclick="tutupModalBatal()"
                            class="h-9 rounded-xl border border-line px-4 text-xs font-semibold text-muted hover:bg-slate-50 transition-colors">
                        Kembali
                    </button>
                    <button type="submit"
                            class="h-9 rounded-xl bg-red-600 px-4 text-xs font-semibold text-white hover:bg-red-700 shadow-xs transition-colors">
                        Ya, Batalkan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tab switching
    const tabAntreanBtn = document.getElementById('tab-antrean-btn');
    const tabBookingBtn = document.getElementById('tab-booking-btn');
    const sectionAntrean = document.getElementById('section-antrean');
    const sectionBooking = document.getElementById('section-booking');

    tabAntreanBtn.addEventListener('click', function () {
        tabAntreanBtn.className = 'tab-btn flex items-center gap-2 border-b-2 border-primary pb-3 px-3 text-sm font-semibold text-primary transition-colors';
        tabBookingBtn.className = 'tab-btn flex items-center gap-2 border-b-2 border-transparent pb-3 px-3 text-sm font-medium text-muted transition-colors hover:text-ink';
        sectionAntrean.classList.remove('hidden');
        sectionBooking.classList.add('hidden');
    });

    tabBookingBtn.addEventListener('click', function () {
        tabBookingBtn.className = 'tab-btn flex items-center gap-2 border-b-2 border-primary pb-3 px-3 text-sm font-semibold text-primary transition-colors';
        tabAntreanBtn.className = 'tab-btn flex items-center gap-2 border-b-2 border-transparent pb-3 px-3 text-sm font-medium text-muted transition-colors hover:text-ink';
        sectionBooking.classList.remove('hidden');
        sectionAntrean.classList.add('hidden');
    });

    // Panggilan Suara (Text-to-Speech)
    function suarakanTeks(teks) {
        if (!('speechSynthesis' in window)) {
            alert('Browser Anda belum mendukung fitur panggilan suara otomatis (Web Speech API).');
            return;
        }

        window.speechSynthesis.cancel(); // Hentikan suara sebelumnya jika ada
        const utterance = new SpeechSynthesisUtterance(teks);
        utterance.lang = 'id-ID';
        utterance.rate = 0.95; // Sedikit lebih santai agar artikulasi jelas di ruang tunggu
        utterance.pitch = 1.0;

        // Coba cari suara Bahasa Indonesia
        const voices = window.speechSynthesis.getVoices();
        const idVoice = voices.find(v => v.lang === 'id-ID' || v.lang.startsWith('id'));
        if (idVoice) {
            utterance.voice = idVoice;
        }

        window.speechSynthesis.speak(utterance);
    }

    // Event listener untuk tombol Panggil
    document.querySelectorAll('.btn-panggil').forEach(button => {
        button.addEventListener('click', function () {
            const visitId = this.dataset.visitId;
            const nomor = this.dataset.nomor || '';
            const nama = this.dataset.nama || '';
            const poli = this.dataset.poli || 'ruang periksa';

            const pesan = `Nomor antrean ${nomor}, atas nama ${nama}, silakan menuju ${poli}.`;

            // Bunyikan langsung
            suarakanTeks(pesan);

            // Kirim log panggilan ke server via AJAX
            fetch(`/dashboard/antrean/${visitId}/panggil`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                }
            }).catch(err => console.warn('Gagal mencatat log panggilan antrean:', err));
        });
    });

    // Cek flash message panggilan suara dari redirect backend jika ada
    @if (session('panggilan_suara'))
        suarakanTeks(@json(session('panggilan_suara')));
    @endif
});

// Modal Batal
const modalBatal = document.getElementById('modal-batal');
const formBatal = document.getElementById('form-batal-antrean');
const modalBatalTitle = document.getElementById('modal-batal-title');

function bukaModalBatal(visitId, nomor, nama) {
    formBatal.action = `/dashboard/antrean/${visitId}/batal`;
    modalBatalTitle.textContent = `Batalkan Antrean #${nomor} (${nama})`;
    modalBatal.classList.remove('hidden');
    document.getElementById('alasan-batal').focus();
}

function tutupModalBatal() {
    modalBatal.classList.add('hidden');
    document.getElementById('alasan-batal').value = '';
}
</script>
@endpush

