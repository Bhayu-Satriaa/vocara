@extends('layouts.app')

@section('judul', 'Dashboard Dokter')

@section('isi')
    <h1 class="mb-1 text-xl font-semibold">Dashboard dokter</h1>
    <p class="mb-6 text-sm text-slate-500">Selamat datang, {{ $user->name }}.</p>

    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="mb-3 font-medium">Tugas yang menunggu dikerjakan</h2>
        <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600">
            <li>Daftar pasien hari ini; scan QR untuk membuka rekam medis.</li>
            <li>Input pemeriksaan format SOAP + tanda vital.</li>
            <li>Diagnosis ICD-10 dan resep elektronik.</li>
            <li>Dikte catatan SOAP (speech-to-text) — menyusul.</li>
        </ul>
        <p class="mt-4 text-xs text-slate-400">
            Belum ada modul di sini — halaman pemeriksaan dikerjakan setelah modul janji temu.
        </p>
    </div>
@endsection
