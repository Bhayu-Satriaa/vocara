@extends('layouts.app')

@section('judul', 'Dashboard Petugas')

@section('isi')
    <h1 class="mb-1 text-xl font-semibold">Dashboard petugas pendaftaran / kasir</h1>
    <p class="mb-6 text-sm text-slate-500">Selamat datang, {{ $user->name }}.</p>

    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="mb-3 font-medium">Tugas yang menunggu dikerjakan</h2>
        <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600">
            <li>Scan QR pasien untuk check-in (kunjungan masuk antrean).</li>
            <li>Verifikasi identitas pasien dengan mencocokkan KTP.</li>
            <li>Daftarkan pasien walk-in (QR dibuat otomatis, bisa dicetak atau dikirim).</li>
            <li>Kelola antrean, konfirmasi booking online.</li>
            <li>Pembayaran dan kwitansi.</li>
        </ul>
        <p class="mt-4 text-xs text-slate-400">
            Belum ada modul di sini — halaman antrean dan scan QR dikerjakan setelah modul janji temu.
        </p>
    </div>
@endsection
