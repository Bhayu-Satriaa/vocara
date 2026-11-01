@extends('layouts.app')

@section('judul', 'Panel Admin')

@section('isi')
<div class="mx-auto max-w-5xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Panel admin</h1>
        <p class="text-sm text-slate-500">Kumpulan fitur yang hanya bisa dipakai admin klinik.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <a href="{{ route('admin.akun-staf.index') }}"
           class="rounded-lg border border-slate-200 bg-white p-5 no-underline transition hover:border-teal-300 hover:shadow-sm">
            <h2 class="font-semibold text-slate-800">Akun staf</h2>
            <p class="mt-1 text-sm text-slate-500">
                Buat dan atur akun dokter, petugas, apoteker, dan admin. Akun nonaktif tidak bisa masuk.
            </p>
        </a>

        @if (\Illuminate\Support\Facades\Route::has('admin.persetujuan-profil.index'))
            <a href="{{ route('admin.persetujuan-profil.index') }}"
               class="rounded-lg border border-slate-200 bg-white p-5 no-underline transition hover:border-teal-300 hover:shadow-sm">
                <h2 class="font-semibold text-slate-800">Persetujuan profil pasien</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Setujui pengajuan data diri dari pasien; nomor rekam medis dan kartu QR terbit otomatis.
                </p>
            </a>
        @endif
    </div>
</div>
@endsection
