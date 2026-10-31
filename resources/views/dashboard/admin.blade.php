@extends('layouts.app')

@section('judul', 'Dashboard Admin')

@section('isi')
    <h1 class="mb-1 text-xl font-semibold">Dashboard admin</h1>
    <p class="mb-6 text-sm text-slate-500">Ringkasan data klinik saat ini.</p>

    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ($statistik as $label => $nilai)
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-2xl font-semibold">{{ $nilai }}</p>
                <p class="text-xs text-slate-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="mb-3 font-medium">Yang bisa dikerjakan sekarang</h2>
        <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600">
            <li><a class="text-teal-700 hover:underline" href="{{ \App\Filament\Resources\Users\UserResource::getUrl() }}">Kelola akun staf</a> (buat akun dokter, petugas, apoteker) — lewat panel admin.</li>
            <li>Data master (poli, dokter, jadwal, tarif, ICD-10, obat) — modul menyusul.</li>
            <li>Laporan dan ekspor PDF/Excel, log aktivitas — modul menyusul.</li>
        </ul>
        <p class="mt-4 text-xs text-slate-400">
            Daftar fitur lengkap per role: docs/FEATURES.md
        </p>
    </div>
@endsection
