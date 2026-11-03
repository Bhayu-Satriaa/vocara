@extends('layouts.app')

@section('judul', 'Akun Staf')
@section('crumb', 'Akun staf')
@section('main-class', 'max-w-none px-6 xl:px-8 pt-9 pb-14 max-[1080px]:px-4 max-[1080px]:pt-7')

@section('isi')
    <div>
        <div class="mb-6">
            <h1 class="text-[24px] leading-tight font-bold tracking-tight text-ink">Akun staf</h1>
            <p class="mt-1 text-sm text-muted">Buat dan ubah akun dokter, petugas, apoteker, dan admin.</p>
        </div>

        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div class="flex flex-1 overflow-x-auto border-b border-line text-[13px] font-medium">
                <a href="{{ route('admin.akun-staf.index') }}"
                   class="-mb-px whitespace-nowrap border-b-2 px-4 py-2.5 no-underline transition {{ $roleFilter === 'semua' ? 'border-primary font-semibold text-primary' : 'border-transparent text-muted hover:text-ink' }}">
                    Semua ({{ $jumlah }})
                </a>
                @foreach (\App\Models\User::ROLE_STAF as $r)
                    <a href="{{ route('admin.akun-staf.index', ['role' => $r]) }}"
                       class="-mb-px whitespace-nowrap border-b-2 px-4 py-2.5 capitalize no-underline transition {{ $roleFilter === $r ? 'border-primary font-semibold text-primary' : 'border-transparent text-muted hover:text-ink' }}">
                        {{ $r }}
                    </a>
                @endforeach
            </div>

            <a href="{{ route('admin.akun-staf.create') }}"
               class="mb-px inline-flex h-[34px] shrink-0 items-center gap-2 rounded-[8px] bg-primary px-3.5 text-[13px] font-semibold text-white no-underline hover:bg-primary-hover">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Tambah akun
            </a>
        </div>

        <div class="overflow-hidden rounded-[14px] border border-line bg-surface shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <div class="border-b border-line px-5 py-3.5">
                <p class="text-[13px] font-medium text-muted">{{ $akun->total() }} akun staf</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-sm capitalize tracking-tight text-ink/80">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Nama</th>
                            <th class="px-5 py-3 font-semibold">Email</th>
                            <th class="px-5 py-3 font-semibold">Role</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="w-[1%] whitespace-nowrap px-5 py-3 text-left font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line-soft">
                        @forelse ($akun as $u)
                            @php
                                $inisial = collect(explode(' ', $u->name))
                                    ->filter()->map(fn ($kata) => mb_substr($kata, 0, 1))->take(2)->implode('');
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-soft text-[11px] font-bold text-primary">
                                            {{ $inisial }}
                                        </span>
                                        <span class="font-medium text-ink">{{ $u->name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-muted">{{ $u->email }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="rounded-md bg-accent-soft px-2.5 py-1 text-[11.5px] font-semibold text-primary capitalize">{{ $u->role }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="rounded-md px-2.5 py-1 text-[11.5px] font-semibold {{ $u->is_active ? 'bg-green-soft text-green-ink' : 'bg-rose-50 text-rose-600' }}">
                                        {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center justify-end">
                                        <a href="{{ route('admin.akun-staf.edit', $u) }}" aria-label="Ubah akun {{ $u->name }}"
                                           class="flex h-8 w-8 items-center justify-center rounded-lg text-muted hover:bg-slate-100 hover:text-ink">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-muted">Belum ada akun staf.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3">
                <p class="text-[12.5px] text-muted">
                    Halaman {{ $akun->currentPage() }} dari {{ $akun->lastPage() }}
                </p>
                @include('admin._pagination', ['paginator' => $akun])
            </div>
        </div>
    </div>
@endsection
