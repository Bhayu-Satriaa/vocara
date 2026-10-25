@extends('layouts.app')

@section('judul', 'Dashboard Apoteker')

@section('isi')
    <h1 class="mb-1 text-xl font-semibold">Dashboard apoteker</h1>
    <p class="mb-6 text-sm text-slate-500">Selamat datang, {{ $user->name }}.</p>

    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="mb-3 font-medium">Tugas yang menunggu dikerjakan</h2>
        <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600">
            <li>Daftar resep masuk dan ubah statusnya (menunggu, disiapkan, selesai).</li>
            <li>Scan QR pasien saat penyerahan obat.</li>
            <li>Manajemen stok obat (masuk, keluar, kedaluwarsa, stok minimum).</li>
            <li>Cetak etiket.</li>
        </ul>
        <p class="mt-4 text-xs text-slate-400">
            Belum ada modul di sini — halaman resep dan stok dikerjakan setelah modul pemeriksaan.
        </p>
    </div>
@endsection
