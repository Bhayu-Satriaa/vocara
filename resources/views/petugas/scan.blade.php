@extends('layouts.app')

@section('judul', 'Scan QR Pasien')
@section('crumb', 'Scan QR pasien')

@section('isi')
    <div class="mx-auto max-w-xl">
        <h1 class="mb-1.5 text-[30px] leading-tight font-bold tracking-tight max-[820px]:text-2xl">
            Scan QR pasien
        </h1>
        <p class="mb-8 text-sm text-muted">
            Pindai QR pada kartu pasien, lalu tempel hasilnya di sini. Setiap pemindaian tercatat di sistem.
        </p>

        <form method="POST" action="{{ route('petugas.scan.cari') }}"
              class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            @csrf

            <span class="mb-4 flex h-11 w-11 items-center justify-center rounded-[11px] bg-accent-soft text-accent">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M3 12h18"/></svg>
            </span>

            <label for="token" class="mb-1.5 block text-[12.5px] font-semibold">Token QR</label>
            <input id="token" name="token" value="{{ old('token') }}" autofocus autocomplete="off"
                   placeholder="cth. 40b17330-1234-4abc-8def-0123456789ab"
                   class="h-10 w-full rounded-[10px] border border-line bg-surface px-3 font-mono text-sm text-ink placeholder:text-faint focus:border-primary focus:ring-2 focus:ring-primary-soft focus:outline-none">

            @error('token')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <button type="submit"
                    class="mt-4 flex h-[38px] w-full cursor-pointer items-center justify-center rounded-[9px] bg-primary text-[13.5px] font-semibold text-white hover:bg-primary-hover">
                Cari pasien
            </button>

            <p class="mt-4 rounded-[10px] border border-dashed border-line bg-slate-50 px-3.5 py-3 text-[12.5px] text-faint">
                Pemindai kamera belum dibuat — sementara token diketik atau ditempel dari hasil scan HP.
            </p>
        </form>
    </div>
@endsection
