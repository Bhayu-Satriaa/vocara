@extends('layouts.app')

@section('judul', 'Dashboard Pasien')
@section('crumb', 'Dashboard Pasien')

@section('isi')
    <div class="mb-8 flex items-start justify-between gap-6 max-[640px]:flex-col">
        <div>
            <h1 class="mb-1.5 text-[30px] font-bold tracking-tight leading-tight max-[820px]:text-2xl text-ink">
                Selamat datang, {{ $user->name }}
            </h1>
            <p class="text-sm text-muted">Portal layanan mandiri pasien. Data medis rahasia dikelola langsung oleh dokter.</p>
        </div>
        @if ($pasien)
            <a href="{{ route('pasien.qr') }}"
               class="inline-flex h-[38px] shrink-0 items-center gap-2 rounded-[9px] bg-primary px-3.5 text-[13.5px] font-semibold text-white no-underline hover:bg-primary-hover">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                Lihat kartu QR
            </a>
        @endif
    </div>

    @if ($pasien)
        <div class="grid items-start gap-6 max-[1080px]:grid-cols-1 lg:grid-cols-[minmax(0,1fr)_340px]">
            {{-- Kolom Kiri: Ringkasan Identitas & Alur Layanan --}}
            <div class="space-y-6">
                {{-- Kartu Ringkasan Tiga Kolom --}}
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-[14px] border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
                        <p class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Nomor rekam medis</p>
                        <p class="mt-1 font-mono text-[19px] font-bold text-ink">{{ $pasien->no_rm }}</p>
                        <p class="mt-0.5 truncate text-xs text-muted">{{ $pasien->nama_lengkap }}</p>
                    </div>

                    <div class="rounded-[14px] border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
                        <p class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Status kartu QR</p>
                        <div class="mt-2">
                            @if ($pasien->sudahTerverifikasi())
                                <span class="rounded-full bg-green-soft px-2.5 py-1 text-[11.5px] font-semibold text-green-ink">
                                    terverifikasi
                                </span>
                            @else
                                <span class="rounded-full bg-amber-soft px-2.5 py-1 text-[11.5px] font-semibold text-amber-ink">
                                    belum terverifikasi
                                </span>
                            @endif
                        </div>
                        <p class="mt-1.5 text-xs text-muted">Cocokkan KTP di loket</p>
                    </div>

                    <div class="rounded-[14px] border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
                        <p class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Nomor WA</p>
                        <p class="mt-1 font-mono text-[17px] font-semibold text-ink">{{ $pasien->no_wa }}</p>
                        <p class="mt-0.5 text-xs text-muted capitalize">{{ $pasien->hubungan_keluarga }}</p>
                    </div>
                </div>

                {{-- Bagian Alur & Langkah Berikutnya --}}
                <section class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-[17px] font-semibold tracking-tight text-ink">Langkah berikutnya</h2>
                            <p class="mt-0.5 text-[13px] text-muted">Alur kunjungan dan penggunaan layanan klinik</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-accent-soft px-2.5 py-1 text-[11.5px] font-semibold text-primary">
                            Alur pasien
                        </span>
                    </div>

                    <ul class="mt-5 divide-y divide-line-soft">
                        <li class="flex gap-3.5 py-3.5 first:pt-0">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-accent-soft text-accent">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-ink">
                                    <a class="text-primary hover:underline" href="{{ route('pasien.qr') }}">Lihat kartu QR</a>
                                </p>
                                <p class="mt-0.5 text-[13px] text-muted">Tunjukkan ke petugas saat datang pertama kali untuk diverifikasi dengan KTP fisik.</p>
                            </div>
                        </li>
                        <li class="flex gap-3.5 py-3.5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-indigo-soft text-indigo-ink">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-ink">Booking jadwal dokter & antrean</p>
                                <p class="mt-0.5 text-[13px] text-muted">Pilih poli dan dokter spesialis sesuai jadwal praktik online.</p>
                            </div>
                        </li>
                        <li class="flex gap-3.5 py-3.5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-green-soft text-green-ink">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 11 2 2 4-4"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-ink">Pemeriksaan, resep obat & pembayaran</p>
                                <p class="mt-0.5 text-[13px] text-muted">Pantau status pemeriksaan medis, pengambilan obat di apotek, dan bukti transaksi kwitansi.</p>
                            </div>
                        </li>
                    </ul>

                    <p class="mt-4 rounded-[10px] border border-dashed border-line bg-slate-50 px-3.5 py-3 text-[12.5px] text-faint">
                        Booking jadwal dokter, riwayat kunjungan, dan chatbot — modul menyusul.
                    </p>
                </section>

                {{-- Banner Chatbot AI Triase --}}
                <div class="flex items-start gap-4 rounded-[14px] border border-line bg-surface p-5 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-accent-soft text-accent">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </span>
                    <div class="flex-1">
                        <h3 class="text-[15px] font-semibold text-ink">Asisten Kesehatan & Triase Mandiri</h3>
                        <p class="mt-0.5 text-[13px] text-muted">
                            Punya keluhan kesehatan tapi bingung memilih poli? Manfaatkan asisten AI kami dengan interaksi teks maupun suara.
                        </p>
                        <p class="mt-2 text-xs font-medium text-amber-ink">
                            * Disclaimer: Chatbot AI bukan pengganti dokter. Keputusan medis tetap di tangan tenaga kesehatan.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Kartu QR Ringkas & Edukasi Verifikasi --}}
            <div class="space-y-6">
                <section class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
                    <span class="mb-3.5 flex h-11 w-11 items-center justify-center rounded-[11px] bg-accent-soft text-accent">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    </span>
                    <h2 class="text-[17px] font-semibold tracking-tight text-ink">Kartu QR Pasien</h2>
                    <p class="mt-2 mb-4 text-[13.5px] text-muted">
                        Tunjukkan kode QR ini ke petugas loket untuk verifikasi identitas dan check-in antrean.
                    </p>

                    <div class="mb-4 flex flex-col items-center justify-center rounded-[10px] border border-line-soft bg-slate-50 p-4">
                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(160)->margin(1)->generate($pasien->qr_token) !!}
                        <p class="mt-3 font-mono text-xs font-semibold text-ink">{{ $pasien->no_rm }}</p>
                        <p class="text-[11px] text-muted">{{ $pasien->nama_lengkap }}</p>
                    </div>

                    <a href="{{ route('pasien.qr') }}"
                       class="flex h-[38px] w-full items-center justify-center rounded-[9px] bg-primary text-[13.5px] font-semibold text-white no-underline hover:bg-primary-hover">
                        Buka Kartu Penuh
                    </a>
                </section>

                @if (! $pasien->sudahTerverifikasi())
                    <div class="rounded-[14px] border border-amber-soft bg-amber-soft/60 p-5">
                        <h3 class="text-sm font-semibold text-amber-ink">Verifikasi KTP Diperlukan</h3>
                        <p class="mt-1 text-[13px] leading-relaxed text-slate-700">
                            Kartu QR kamu masih <strong>belum terverifikasi</strong>. Verifikasi dilakukan petugas
                            dengan mencocokkan KTP saat kunjungan pertama.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- State Onboarding: Profil Belum Lengkap --}}
        <div class="rounded-[14px] border border-amber-soft bg-amber-soft/40 p-8 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <div class="max-w-xl">
                <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-[12px] bg-amber-soft text-amber-ink">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
                <h2 class="text-[20px] font-bold text-ink">Lengkapi profil dulu</h2>
                <p class="mt-2 text-[14px] leading-relaxed text-slate-700">
                    Nomor WA wajib diisi supaya kartu QR dan booking bisa dipakai. Verifikasinya dilakukan
                    petugas saat kamu datang, bukan lewat OTP.
                </p>
                <div class="mt-6">
                    <a href="{{ route('pasien.profil') }}"
                       class="inline-flex h-[38px] items-center gap-2 rounded-[9px] bg-primary px-4 text-[13.5px] font-semibold text-white no-underline hover:bg-primary-hover">
                        Lengkapi profil
                    </a>
                </div>
            </div>
        </div>
    @endif
@endsection
