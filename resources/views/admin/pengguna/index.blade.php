@extends('layouts.app')

@section('judul', 'Akun Staf')

@section('isi')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold">Akun staf</h1>
            <p class="text-sm text-slate-500">
                Akun pasien tidak dibuat di sini — pasien mendaftar sendiri lewat Google.
            </p>
        </div>
        <a href="{{ route('admin.pengguna.baru') }}"
           class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
            Tambah akun
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($pengguna as $staf)
                    <tr>
                        <td class="px-4 py-3">{{ $staf->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $staf->email }}</td>
                        <td class="px-4 py-3">{{ $staf->role }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs
                                {{ $staf->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $staf->is_active ? 'aktif' : 'nonaktif' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-slate-400">Belum ada akun staf.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $pengguna->links() }}
    </div>
@endsection
