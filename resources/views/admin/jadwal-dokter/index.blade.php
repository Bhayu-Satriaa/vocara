@extends('layouts.app')

@section('judul', 'Jadwal Dokter')
@section('crumb', 'Jadwal dokter')
@section('main-class', 'max-w-none px-6 xl:px-8 pt-9 pb-14 max-[1080px]:px-4 max-[1080px]:pt-7')

@section('isi')
    <div>
        <div class="mb-6">
            <h1 class="text-[24px] leading-tight font-bold tracking-tight text-ink">Jadwal dokter</h1>
            <p class="mt-1 text-sm text-muted">Pilih dokter untuk mengatur hari praktiknya.</p>
        </div>

        <div class="overflow-hidden rounded-[14px] border border-line bg-surface shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <div class="border-b border-line px-5 py-3.5">
                <p class="text-[13px] font-medium text-muted">{{ $dokter->total() }} dokter</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-sm capitalize tracking-tight text-ink/80">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Nama</th>
                            <th class="px-5 py-3 font-semibold">Poli</th>
                            <th class="px-5 py-3 font-semibold">Hari aktif</th>
                            <th class="w-[1%] whitespace-nowrap px-5 py-3 text-left font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line-soft">
                        @forelse ($dokter as $d)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3.5 font-medium text-ink">{{ $d->user->name ?? '-' }}</td>
                                <td class="px-5 py-3.5 text-muted">{{ $d->poli->nama ?? '-' }}</td>
                                <td class="px-5 py-3.5 text-muted">{{ $d->jadwal_aktif_count }} hari</td>
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('admin.jadwal-dokter.show', $d) }}"
                                       class="text-[13px] font-medium text-primary no-underline hover:underline">
                                        Atur jadwal
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-sm text-muted">Belum ada dokter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3">
                <p class="text-[12.5px] text-muted">
                    Halaman {{ $dokter->currentPage() }} dari {{ $dokter->lastPage() }}
                </p>
                @include('admin._pagination', ['paginator' => $dokter])
            </div>
        </div>
    </div>
@endsection
