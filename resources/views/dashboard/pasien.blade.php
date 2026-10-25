@extends('layouts.app')

@section('judul', 'Dashboard Pasien')

@section('isi')
    <h1 class="mb-1 text-xl font-semibold">Selamat datang, {{ $user->name }}</h1>
    <p class="mb-6 text-sm text-slate-500">Ini halaman pasien. Data medis tidak ditampilkan di sini.</p>

    @if ($pasien)
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">Nomor rekam medis</p>
                <p class="text-lg font-semibold">{{ $pasien->no_rm }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">Status kartu QR</p>
                <p class="text-lg font-semibold">
                    {{ $pasien->sudahTerverifikasi() ? 'terverifikasi' : 'belum terverifikasi' }}
                </p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">Nomor WA</p>
                <p class="text-lg font-semibold">{{ $pasien->no_wa }}</p>
            </div>
        </div>

        <div class="mb-6 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="mb-3 font-medium">Langkah berikutnya</h2>
            <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600">
                <li><a class="text-teal-700 hover:underline" href="{{ route('pasien.qr') }}">Lihat kartu QR</a> — tunjukkan ke petugas saat datang pertama kali untuk diverifikasi dengan KTP.</li>
                <li>Booking jadwal dokter, riwayat kunjungan, dan chatbot — modul menyusul.</li>
            </ul>
        </div>

        @if (! $pasien->sudahTerverifikasi())
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Kartu QR kamu masih <strong>belum terverifikasi</strong>. Verifikasi dilakukan petugas
                dengan mencocokkan KTP saat kunjungan pertama.
            </div>
        @endif
    @else
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-6">
            <h2 class="mb-2 font-medium text-amber-900">Lengkapi profil dulu</h2>
            <p class="mb-4 text-sm text-amber-800">
                Nomor WA wajib diisi supaya kartu QR dan booking bisa dipakai. Verifikasinya dilakukan
                petugas saat kamu datang, bukan lewat OTP.
            </p>
            <a href="{{ route('pasien.profil') }}"
               class="inline-block rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                Lengkapi profil
            </a>
        </div>
    @endif
@endsection
