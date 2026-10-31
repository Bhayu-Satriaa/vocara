@extends('layouts.app')

@section('judul', 'Dashboard Petugas')

@section('isi')
    <div class="mb-8 flex items-start justify-between gap-6 max-[640px]:flex-col">
        <div>
            <h1 class="mb-1.5 text-[30px] leading-tight font-bold tracking-tight max-[820px]:text-2xl">
                Dashboard petugas pendaftaran / kasir
            </h1>
            <p class="text-sm text-muted">Selamat datang, {{ $user->name }}.</p>
        </div>
        <a href="{{ route('petugas.scan') }}"
           class="inline-flex h-[38px] shrink-0 items-center gap-2 rounded-[9px] bg-primary px-3.5 text-[13.5px] font-semibold text-white no-underline hover:bg-primary-hover">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M3 12h18"/></svg>
            Scan QR pasien
        </a>
    </div>

    <div class="grid items-start gap-6 max-[1080px]:grid-cols-1 lg:grid-cols-[minmax(0,1fr)_340px]">
        <section class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-[17px] font-semibold tracking-tight">Tugas yang menunggu dikerjakan</h2>
                    <p class="mt-0.5 text-[13px] text-muted">Wilayah kerja petugas pendaftaran / kasir</p>
                </div>
                <span class="shrink-0 rounded-full bg-accent-soft px-2.5 py-1 text-[11.5px] font-semibold text-primary">
                    5 tugas
                </span>
            </div>

            <ul class="mt-5 divide-y divide-line-soft">
                <li class="flex gap-3.5 py-3.5 first:pt-0">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-accent-soft text-accent">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M3 12h18"/></svg>
                    </span>
                    <span class="pt-2 text-sm font-medium">Scan QR pasien untuk check-in (kunjungan masuk antrean).</span>
                </li>
                <li class="flex gap-3.5 py-3.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-amber-soft text-amber-ink">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 11 2 2 4-4"/></svg>
                    </span>
                    <span class="pt-2 text-sm font-medium">Verifikasi identitas pasien dengan mencocokkan KTP.</span>
                </li>
                <li class="flex gap-3.5 py-3.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-green-soft text-green-ink">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                    </span>
                    <span class="pt-2 text-sm font-medium">Daftarkan pasien walk-in (QR dibuat otomatis, bisa dicetak atau dikirim).</span>
                </li>
                <li class="flex gap-3.5 py-3.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-indigo-soft text-indigo-ink">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/></svg>
                    </span>
                    <span class="pt-2 text-sm font-medium">Kelola antrean, konfirmasi booking online.</span>
                </li>
                <li class="flex gap-3.5 py-3.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-accent-soft text-accent">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    </span>
                    <span class="pt-2 text-sm font-medium">Pembayaran dan kwitansi.</span>
                </li>
            </ul>

            <p class="mt-4 rounded-[10px] border border-dashed border-line bg-slate-50 px-3.5 py-3 text-[12.5px] text-faint">
                Belum ada modul di sini — halaman antrean dan scan QR dikerjakan setelah modul janji temu.
            </p>
        </section>

        <section class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <span class="mb-3.5 flex h-11 w-11 items-center justify-center rounded-[11px] bg-accent-soft text-accent">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M3 12h18"/></svg>
            </span>
            <h2 class="text-[17px] font-semibold tracking-tight">Scan QR pasien</h2>
            <p class="mt-2 mb-4 text-[13.5px] text-muted">
                Pindai QR pada kartu pasien, lalu tempel hasilnya di sini. Setiap pemindaian tercatat di sistem.
            </p>
            <a href="{{ route('petugas.scan') }}"
               class="flex h-[38px] w-full items-center justify-center rounded-[9px] bg-primary text-[13.5px] font-semibold text-white no-underline hover:bg-primary-hover">
                Cari pasien
            </a>
            <p class="mt-3.5 text-xs text-faint">
                Pemindai kamera belum dibuat — sementara token diketik atau ditempel dari hasil scan HP.
            </p>
        </section>
    </div>
@endsection
