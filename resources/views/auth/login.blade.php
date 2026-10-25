@extends('layouts.app')

@section('judul', 'Masuk Staf')

@section('isi')
    <div class="mx-auto mt-10 max-w-md">
        <h1 class="mb-1 text-xl font-semibold">Masuk untuk staf klinik</h1>
        <p class="mb-6 text-sm text-slate-500">
            Akun staf dibuat oleh admin. Pasien tidak masuk lewat halaman ini — pasien memakai tombol Google.
        </p>

        <form method="POST" action="{{ route('login.masuk') }}"
              class="space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="ingat" value="1" class="rounded border-slate-300">
                Ingat saya
            </label>

            <button type="submit"
                    class="w-full rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                Masuk
            </button>
        </form>

        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6">
            <p class="mb-3 text-sm text-slate-600">Kamu pasien?</p>
            <a href="{{ route('google.arahkan') }}"
               class="block w-full rounded-md border border-slate-300 px-4 py-2 text-center text-sm font-medium hover:bg-slate-50">
                Masuk dengan Google
            </a>
            <p class="mt-3 text-xs text-slate-400">
                Pertama kali masuk, kamu diarahkan melengkapi profil (nomor WA wajib) dan kartu QR
                dibuat otomatis.
            </p>
        </div>
    </div>
@endsection
