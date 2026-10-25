@extends('layouts.app')

@section('judul', 'Kartu QR')

@section('isi')
    <div class="mx-auto max-w-xl">
        <h1 class="mb-1 text-xl font-semibold">Kartu QR kamu</h1>
        <p class="mb-6 text-sm text-slate-500">
            Tunjukkan kode ini ke petugas. Setiap pemindaian tercatat di sistem.
        </p>

        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <dl class="mb-4 space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Nama</dt>
                    <dd class="font-medium">{{ $pasien->nama_lengkap }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Nomor rekam medis</dt>
                    <dd class="font-medium">{{ $pasien->no_rm }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Status</dt>
                    <dd class="font-medium">
                        {{ $pasien->sudahTerverifikasi() ? 'terverifikasi' : 'belum terverifikasi' }}
                    </dd>
                </div>
            </dl>

            <p class="mb-2 text-xs text-slate-500">Token QR (isinya token acak — bukan NIK, bukan data medis):</p>
            <p class="break-all rounded-md bg-slate-50 px-3 py-2 font-mono text-sm">{{ $pasien->qr_token }}</p>

            <p class="mt-4 text-xs text-slate-400">
                Gambar QR untuk diunduh dan dicetak belum dibuat: butuh paket pembuat QR yang harus
                disetujui tim dulu. Sementara ini tunjukkan tokennya ke petugas.
            </p>
        </div>

        @if (! $pasien->sudahTerverifikasi())
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Kartu ini akan diverifikasi petugas saat kamu datang pertama kali (pencocokan KTP).
            </div>
        @endif
    </div>
@endsection
