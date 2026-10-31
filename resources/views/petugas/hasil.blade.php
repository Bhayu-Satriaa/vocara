@extends('layouts.app')

@section('judul', 'Data Pasien Hasil Scan')

@section('isi')
    <div class="mx-auto max-w-2xl">
        <div class="mb-4 flex items-center justify-between gap-4">
            <h1 class="text-xl font-semibold">Data pasien hasil scan</h1>
            <a href="{{ route('petugas.scan') }}" class="text-sm hover:underline">Scan lagi</a>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">Nomor rekam medis</dt>
                    <dd class="font-medium">{{ $pasien->no_rm }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Nama</dt>
                    <dd class="font-medium">{{ $pasien->nama_lengkap }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Tanggal lahir</dt>
                    <dd>{{ $pasien->tgl_lahir?->format('d-m-Y') }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Jenis kelamin</dt>
                    <dd>{{ $pasien->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Nomor WA</dt>
                    <dd>{{ $pasien->no_wa }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-slate-500">Alamat</dt>
                    <dd>{{ $pasien->alamat ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Status identitas</dt>
                    <dd class="font-medium">
                        {{ $pasien->sudahTerverifikasi() ? 'terverifikasi' : 'belum terverifikasi' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Diverifikasi</dt>
                    <dd>
                        @if ($pasien->sudahTerverifikasi())
                            {{ $pasien->verifier?->name }} &middot; {{ $pasien->verified_at?->format('d-m-Y H:i') }}
                        @else
                            -
                        @endif
                    </dd>
                </div>
            </dl>

            @unless ($pasien->sudahTerverifikasi())
                <form method="POST" action="{{ route('petugas.scan.verifikasi', $pasien) }}" class="mt-6 border-t border-slate-200 pt-5">
                    @csrf
                    <p class="mb-3 text-sm text-slate-600">
                        Cocokkan nama, tanggal lahir, dan alamat di atas dengan KTP pasien. Tandai terverifikasi hanya
                        kalau semuanya sesuai.
                    </p>
                    <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm text-white hover:bg-emerald-600">
                        KTP sesuai — tandai terverifikasi
                    </button>
                </form>
            @endunless
        </div>

        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="mb-3 font-medium">Riwayat scan terakhir</h2>

            @forelse ($riwayatScan as $scan)
                <div class="flex justify-between gap-4 border-b border-slate-100 py-2 text-sm last:border-0">
                    <span>{{ $scan->konteks }} &middot; {{ $scan->petugas?->name }}</span>
                    <span class="text-slate-500">{{ $scan->created_at?->format('d-m-Y H:i') }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">Belum ada riwayat scan.</p>
            @endforelse
        </div>
    </div>
@endsection
