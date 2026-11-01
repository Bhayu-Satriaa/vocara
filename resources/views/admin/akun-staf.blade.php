@extends('layouts.app')

@section('judul', 'Akun Staf')

@section('isi')
<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">Akun staf</h1>
            <p class="text-sm text-slate-500">
                Akun dokter, petugas, apoteker, dan admin. Akun pasien tidak dikelola di sini — pasien mendaftar sendiri lewat Google.
            </p>
        </div>
        <div class="shrink-0">
            <a href="{{ route('admin.akun-staf.create') }}"
               class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700">
                Tambah akun staf
            </a>
        </div>
    </div>

    {{-- Filter role --}}
    <div class="mb-6 flex flex-wrap border-b border-slate-200 text-sm font-medium">
        <a href="{{ route('admin.akun-staf.index', ['role' => 'semua']) }}"
           class="border-b-2 px-4 py-2.5 transition {{ $roleFilter === 'semua' ? 'border-teal-600 font-semibold text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Semua
        </a>
        @foreach (\App\Models\User::ROLE_STAF as $role)
            <a href="{{ route('admin.akun-staf.index', ['role' => $role]) }}"
               class="border-b-2 px-4 py-2.5 capitalize transition {{ $roleFilter === $role ? 'border-teal-600 font-semibold text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                {{ $role }}
            </a>
        @endforeach
    </div>

    @if ($akun->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center">
            <h3 class="text-sm font-medium text-slate-900">Belum ada akun staf</h3>
            <p class="mt-1 text-xs text-slate-500">Tidak ada akun staf dengan filter ini.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($akun as $staf)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $staf->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $staf->email }}</td>
                            <td class="px-4 py-3 capitalize text-slate-600">{{ $staf->role }}</td>
                            <td class="px-4 py-3">
                                @if ($staf->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Aktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-400/20">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.akun-staf.edit', $staf) }}"
                                       class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        Ubah
                                    </a>
                                    <form method="POST" action="{{ route('admin.akun-staf.toggle-aktif', $staf) }}"
                                          onsubmit="return confirm('{{ $staf->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $staf->email }}?');">
                                        @csrf
                                        <button type="submit"
                                                class="rounded-md border px-3 py-1.5 text-xs font-medium {{ $staf->is_active ? 'border-rose-300 bg-white text-rose-700 hover:bg-rose-50' : 'border-emerald-300 bg-white text-emerald-700 hover:bg-emerald-50' }}">
                                            {{ $staf->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $akun->links() }}
        </div>
    @endif
</div>
@endsection
