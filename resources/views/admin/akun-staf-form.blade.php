@extends('layouts.app')

@section('judul', $staf->exists ? 'Ubah Akun Staf' : 'Tambah Akun Staf')

@section('isi')
<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">
            {{ $staf->exists ? 'Ubah akun staf' : 'Tambah akun staf' }}
        </h1>
        <p class="text-sm text-slate-500">
            {{ $staf->exists
                ? "Perbarui data akun {$staf->email}. Kosongkan password kalau tidak ingin menggantinya."
                : 'Akun dibuat oleh admin; staf tidak mendaftar sendiri dan tidak memakai Google.' }}
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-medium">Periksa kembali isian berikut:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $staf->exists ? route('admin.akun-staf.update', $staf) : route('admin.akun-staf.store') }}"
          class="rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        @if ($staf->exists)
            @method('PUT')
        @endif

        <div class="space-y-5">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama</label>
                <input id="name" name="name" type="text" maxlength="100" required
                       value="{{ old('name', $staf->name) }}"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" maxlength="150" required
                       value="{{ old('email', $staf->email) }}"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
            </div>

            <div>
                <label for="role" class="mb-1 block text-sm font-medium text-slate-700">Role</label>
                <select id="role" name="role" required
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm capitalize focus:border-teal-500 focus:outline-none">
                    @foreach (\App\Models\User::ROLE_STAF as $role)
                        <option value="{{ $role }}" @selected(old('role', $staf->role) === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">
                    Password {{ $staf->exists ? '(opsional)' : '' }}
                </label>
                <input id="password" name="password" type="password" minlength="8" {{ $staf->exists ? '' : 'required' }}
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
                <p class="mt-1 text-xs text-slate-500">Minimal 8 karakter. Kosongkan kalau tidak ingin mengganti password.</p>
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Ulangi password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="8"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
            </div>

            <label for="is_active" class="flex items-start gap-3 rounded-md bg-slate-50 p-3">
                {{-- Nilai default saat checkbox tidak dicentang. --}}
                <input type="hidden" name="is_active" value="0">
                <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $staf->exists ? $staf->is_active : true))
                       class="mt-0.5 h-4 w-4 rounded border-slate-300">
                <span class="text-sm">
                    <span class="block font-medium text-slate-700">Aktif</span>
                    <span class="block text-xs text-slate-500">Akun nonaktif tidak bisa masuk, baik ke dashboard maupun panel admin.</span>
                </span>
            </label>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('admin.akun-staf.index') }}"
               class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Batal
            </a>
            <button type="submit"
                    class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700">
                Simpan
            </button>
        </div>
    </form>
</div>
@endsection
