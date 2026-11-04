@extends('layouts.app')

@section('judul', 'Jadwal Dokter')
@section('crumb', 'Jadwal dokter')
@section('main-class', 'max-w-none px-6 xl:px-8 pt-9 pb-14 max-[1080px]:px-4 max-[1080px]:pt-7')

@section('isi')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('admin.jadwal-dokter.index') }}" class="text-[13px] font-medium text-primary no-underline hover:underline">
                &larr; Kembali ke daftar dokter
            </a>
            <h1 class="mt-2 text-[24px] leading-tight font-bold tracking-tight text-ink">{{ $dokter->user->name ?? '-' }}</h1>
            <p class="mt-1 text-sm text-muted">{{ $dokter->poli->nama ?? '-' }}{{ $dokter->spesialisasi ? ' &middot; '.$dokter->spesialisasi : '' }}</p>
        </div>

        <div class="space-y-3">
            @forelse ($jadwal as $j)
                <form method="POST" action="{{ route('admin.jadwal-dokter.update', [$dokter, $j]) }}"
                      class="rounded-[14px] border border-line bg-surface p-4 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
                    @csrf
                    @method('PUT')

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-[15px] font-semibold text-ink">{{ $j->hari }}</p>
                        <label class="flex items-center gap-2 text-[12.5px] text-muted">
                            <input type="checkbox" name="is_active" value="1" @checked($j->is_active)
                                   class="h-4 w-4 rounded border-line accent-[#1d4ed8]">
                            Aktif
                        </label>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <label class="block text-[12.5px] text-muted">Mulai
                            <input type="time" name="jam_mulai" value="{{ substr($j->jam_mulai, 0, 5) }}" required
                                   class="mt-1 block h-[34px] w-full rounded-[8px] border border-line bg-white px-2.5 text-[13px] text-ink focus:border-primary focus:outline-none">
                        </label>
                        <label class="block text-[12.5px] text-muted">Selesai
                            <input type="time" name="jam_selesai" value="{{ substr($j->jam_selesai, 0, 5) }}" required
                                   class="mt-1 block h-[34px] w-full rounded-[8px] border border-line bg-white px-2.5 text-[13px] text-ink focus:border-primary focus:outline-none">
                        </label>
                        <label class="block text-[12.5px] text-muted">Slot (mnt)
                            <input type="number" name="durasi_slot" value="{{ $j->durasi_slot }}" min="5" max="480" required
                                   class="mt-1 block h-[34px] w-full rounded-[8px] border border-line bg-white px-2.5 text-[13px] text-ink focus:border-primary focus:outline-none">
                        </label>
                        <label class="block text-[12.5px] text-muted">Kuota
                            <input type="number" name="kuota" value="{{ $j->kuota }}" min="1" max="500" required
                                   class="mt-1 block h-[34px] w-full rounded-[8px] border border-line bg-white px-2.5 text-[13px] text-ink focus:border-primary focus:outline-none">
                        </label>
                    </div>
                    <input type="hidden" name="hari" value="{{ $j->hari }}">

                    <div class="mt-3 flex items-center justify-end gap-2">
                        <button type="submit"
                                class="inline-flex h-[32px] items-center rounded-[8px] bg-primary px-3.5 text-[12.5px] font-semibold text-white hover:bg-primary-hover">
                            Simpan
                        </button>
                        <button type="submit" form="hapus-{{ $j->id }}"
                                onclick="return confirm('Hapus jadwal hari {{ $j->hari }}?');"
                                class="inline-flex h-[32px] items-center rounded-[8px] border border-line px-3.5 text-[12.5px] font-medium text-rose-600 hover:bg-rose-50">
                            Hapus
                        </button>
                    </div>
                </form>
                <form id="hapus-{{ $j->id }}" method="POST" action="{{ route('admin.jadwal-dokter.destroy', [$dokter, $j]) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @empty
                <div class="rounded-[14px] border border-dashed border-line bg-surface p-10 text-center text-sm text-muted">
                    Belum ada jadwal untuk dokter ini.
                </div>
            @endforelse
        </div>

        @if (count($hariTersedia) > 0)
            <form method="POST" action="{{ route('admin.jadwal-dokter.store', $dokter) }}"
                  class="mt-6 rounded-[14px] border border-line bg-surface p-4 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
                @csrf
                <p class="text-[15px] font-semibold text-ink">Tambah hari praktik</p>

                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <label class="block text-[12.5px] text-muted">Hari
                        <select name="hari" required
                                class="mt-1 block h-[34px] w-full rounded-[8px] border border-line bg-white px-2.5 text-[13px] text-ink focus:border-primary focus:outline-none">
                            @foreach ($hariTersedia as $h)
                                <option value="{{ $h }}">{{ $h }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-[12.5px] text-muted">Mulai
                        <input type="time" name="jam_mulai" value="08:00" required
                               class="mt-1 block h-[34px] w-full rounded-[8px] border border-line bg-white px-2.5 text-[13px] text-ink focus:border-primary focus:outline-none">
                    </label>
                    <label class="block text-[12.5px] text-muted">Selesai
                        <input type="time" name="jam_selesai" value="12:00" required
                               class="mt-1 block h-[34px] w-full rounded-[8px] border border-line bg-white px-2.5 text-[13px] text-ink focus:border-primary focus:outline-none">
                    </label>
                    <label class="block text-[12.5px] text-muted">Slot (mnt)
                        <input type="number" name="durasi_slot" value="30" min="5" max="480" required
                               class="mt-1 block h-[34px] w-full rounded-[8px] border border-line bg-white px-2.5 text-[13px] text-ink focus:border-primary focus:outline-none">
                    </label>
                    <label class="block text-[12.5px] text-muted">Kuota
                        <input type="number" name="kuota" value="8" min="1" max="500" required
                               class="mt-1 block h-[34px] w-full rounded-[8px] border border-line bg-white px-2.5 text-[13px] text-ink focus:border-primary focus:outline-none">
                    </label>
                </div>
                <div class="mt-3">
                    <button type="submit"
                            class="inline-flex h-[34px] items-center gap-2 rounded-[8px] bg-primary px-3.5 text-[13px] font-semibold text-white hover:bg-primary-hover">
                        Tambah jadwal
                    </button>
                </div>
            </form>
        @endif
    </div>
@endsection
