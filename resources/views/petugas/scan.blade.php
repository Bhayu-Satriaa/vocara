@extends('layouts.app')

@section('judul', 'Scan QR Pasien')

@section('isi')
    <div class="mx-auto max-w-xl">
        <h1 class="mb-1 text-xl font-semibold">Scan QR pasien</h1>
        <p class="mb-6 text-sm text-slate-500">
            Pindai QR pada kartu pasien, lalu tempel hasilnya di sini. Setiap pemindaian tercatat di sistem.
        </p>

        <form method="POST" action="{{ route('petugas.scan.cari') }}" class="rounded-lg border border-slate-200 bg-white p-6">
            @csrf

            <label for="token" class="mb-1 block text-sm font-medium">Token QR</label>
            <input id="token" name="token" value="{{ old('token') }}" autofocus autocomplete="off"
                   placeholder="cth. 40b17330-1234-4abc-8def-0123456789ab"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-sm">

            @error('token')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <button type="submit" class="mt-4 rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-700">
                Cari pasien
            </button>
        </form>

        <p class="mt-4 text-xs text-slate-400">
            Pemindai kamera belum dibuat — sementara token diketik atau ditempel dari hasil scan HP.
        </p>
    </div>
@endsection
