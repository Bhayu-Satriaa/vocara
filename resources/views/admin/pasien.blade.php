@extends('layouts.app')

@section('judul', 'Data Pasien')
@section('crumb', 'Data pasien')
@section('main-class', 'max-w-none px-6 xl:px-8 pt-9 pb-14 max-[1080px]:px-4 max-[1080px]:pt-7')

@section('isi')
    <div>
        <div class="mb-6">
            <h1 class="text-[24px] leading-tight font-bold tracking-tight text-ink">Data pasien</h1>
            <p class="mt-1 text-sm text-muted">Data master semua pasien terdaftar di klinik.</p>
        </div>

        <div class="overflow-hidden rounded-[14px] border border-line bg-surface shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-3.5">
                <p class="text-[13px] font-medium text-muted">{{ $pasien->total() }} pasien</p>
                <form method="GET" action="{{ route('admin.pasien.index') }}" class="flex items-center gap-2">
                    <select name="status" onchange="this.form.requestSubmit()"
                            class="h-[34px] rounded-[8px] border border-line bg-white px-2.5 text-[13px] text-ink focus:border-primary focus:outline-none">
                        <option value="semua" {{ $status === 'semua' ? 'selected' : '' }}>Semua status</option>
                        <option value="belum" {{ $status === 'belum' ? 'selected' : '' }}>Belum</option>
                        <option value="terverifikasi" {{ $status === 'terverifikasi' ? 'selected' : '' }}>Terverifikasi</option>
                    </select>
                    <input type="search" name="cari" value="{{ $cari }}" placeholder="Cari nama, No RM, atau WA..."
                           class="h-[34px] w-64 rounded-[8px] border border-line bg-white px-3 text-[13px] text-ink placeholder:text-faint focus:border-primary focus:outline-none">
                    <button type="submit"
                            class="inline-flex h-[34px] items-center rounded-[8px] bg-primary px-3.5 text-[13px] font-semibold text-white hover:bg-primary-hover">
                        Cari
                    </button>
                    @if ($cari !== '' || $status !== 'semua')
                        <a href="{{ route('admin.pasien.index') }}"
                           class="inline-flex h-[34px] items-center rounded-[8px] border border-line px-3 text-[13px] font-medium text-muted no-underline hover:bg-slate-50">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-sm capitalize tracking-tight text-ink/80">
                        <tr>
                            <th class="px-5 py-3 font-semibold">No RM</th>
                            <th class="px-5 py-3 font-semibold">Nama</th>
                            <th class="px-5 py-3 font-semibold">No WA</th>
                            <th class="px-5 py-3 font-semibold">Tgl lahir</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="w-[1%] whitespace-nowrap px-5 py-3 text-left font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line-soft">
                        @forelse ($pasien as $p)
                            <tr class="hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-[12.5px] text-muted">{{ $p->no_rm }}</td>
                                <td class="px-5 py-3.5 font-medium text-ink">{{ $p->nama_lengkap }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-muted">{{ $p->no_wa }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-muted">{{ $p->tgl_lahir?->format('d/m/Y') }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="rounded-md px-2.5 py-1 text-[11.5px] font-semibold {{ $p->sudahTerverifikasi() ? 'bg-green-soft text-green-ink' : 'bg-amber-soft text-amber-ink' }}">
                                        {{ $p->sudahTerverifikasi() ? 'Terverifikasi' : 'Belum' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('admin.pasien.show', $p) }}"
                                       class="text-[13px] font-medium text-primary no-underline hover:underline">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-muted">
                                    @if ($cari !== '')
                                        Tidak ada pasien cocok dengan "{{ $cari }}".
                                    @else
                                        Belum ada data pasien.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3">
                <p class="text-[12.5px] text-muted">
                    Halaman {{ $pasien->currentPage() }} dari {{ $pasien->lastPage() }}
                </p>
                @include('admin._pagination', ['paginator' => $pasien])
            </div>
        </div>
    </div>
@endsection
