@extends('layouts.app')

@section('judul', $judul)

@section('isi')
    <div class="mx-auto max-w-xl">
        <div class="mb-4 flex items-center justify-between gap-4">
            <h1 class="text-xl font-semibold">{{ $judul }}</h1>
            <a href="{{ route('pasien.profil') }}" class="text-sm hover:underline">Kembali ke profil</a>
        </div>

        <p class="mb-6 text-sm text-slate-500">{{ $catatan }}</p>

        @include('pasien._form-profil', [
            'aksi' => $aksi,
            'metode' => $metode,
            'tombol' => $tombol,
            'pilihUtama' => $pilihUtama,
            'namaDefault' => $namaDefault,
            'hubungan' => $hubungan,
        ])
    </div>
@endsection
