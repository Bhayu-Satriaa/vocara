@extends('layouts.app')

@section('judul', 'Tambah Akun Staf')

@section('isi')
    <div class="mx-auto max-w-xl">
        <h1 class="mb-1 text-xl font-semibold">Tambah akun staf</h1>
        <p class="mb-6 text-sm text-slate-500">
            Akun baru bisa langsung dipakai login. Sampaikan password awal ke yang bersangkutan.
        </p>

        <form method="POST" action="{{ route('admin.pengguna.simpan') }}"
              class="space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            @csrf

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Nama</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="role" class="mb-1 block text-sm font-medium">Role</label>
                <select id="role" name="role" required
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @foreach ($daftarRole as $role)
                        <option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>
                    @endforeach
                </select>
                @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Password awal</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium">Ulangi password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    Simpan
                </button>
                <a href="{{ route('admin.pengguna') }}" class="text-sm text-slate-500 hover:underline">Batal</a>
            </div>
        </form>
    </div>
@endsection
