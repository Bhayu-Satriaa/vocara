@extends('layouts.app')

@section('judul', 'Pengetahuan Chatbot')
@section('crumb', 'Pengetahuan chatbot')
@section('main-class', 'max-w-none px-6 xl:px-8 pt-9 pb-14 max-[1080px]:px-4 max-[1080px]:pt-7')

@section('isi')
    <div>
        <div class="mb-6">
            <h1 class="text-[24px] leading-tight font-bold tracking-tight text-ink">Pengetahuan chatbot</h1>
            <p class="mt-1 text-sm text-muted">Pertanyaan pasien dan jawaban yang dipakai chatbot.</p>
        </div>

        <div class="overflow-hidden rounded-[14px] border border-line bg-surface shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-3.5">
                <p class="text-[13px] font-medium text-muted">{{ $pengetahuan->total() }} pengetahuan</p>
                <a href="{{ route('admin.pengetahuan-chatbot.create') }}"
                   class="inline-flex h-[34px] items-center gap-2 rounded-[8px] bg-primary px-3.5 text-[13px] font-semibold text-white no-underline hover:bg-primary-hover">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah pengetahuan
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-sm capitalize tracking-tight text-ink/80">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Kategori</th>
                            <th class="px-5 py-3 font-semibold">Pertanyaan</th>
                            <th class="px-5 py-3 font-semibold">Jawaban</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="w-[1%] whitespace-nowrap px-5 py-3 text-left font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line-soft">
                        @forelse ($pengetahuan as $item)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3.5">
                                    <span class="rounded-md bg-accent-soft px-2.5 py-1 text-[11.5px] font-semibold text-primary">{{ $item->kategori }}</span>
                                </td>
                                <td class="max-w-[220px] px-5 py-3.5 font-medium text-ink">{{ $item->pertanyaan }}</td>
                                <td class="max-w-[280px] px-5 py-3.5 text-muted">{{ \Illuminate\Support\Str::limit($item->jawaban, 70) }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="rounded-md px-2.5 py-1 text-[11.5px] font-semibold {{ $item->is_active ? 'bg-green-soft text-green-ink' : 'bg-rose-50 text-rose-600' }}">
                                        {{ $item->is_active ? 'Dipakai' : 'Tidak dipakai' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.pengetahuan-chatbot.edit', $item) }}" aria-label="Ubah pengetahuan {{ $item->pertanyaan }}"
                                           class="flex h-8 w-8 items-center justify-center rounded-lg text-muted hover:bg-slate-100 hover:text-ink">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                        </a>
                                        <form method="POST" action="{{ route('admin.pengetahuan-chatbot.destroy', $item) }}"
                                              onsubmit="return confirm('Hapus pengetahuan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" aria-label="Hapus pengetahuan {{ $item->pertanyaan }}"
                                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-muted hover:bg-rose-50 hover:text-rose-600">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-muted">Belum ada pengetahuan chatbot.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3">
                <p class="text-[12.5px] text-muted">
                    Halaman {{ $pengetahuan->currentPage() }} dari {{ $pengetahuan->lastPage() }}
                </p>
                @include('admin._pagination', ['paginator' => $pengetahuan])
            </div>
        </div>
    </div>
@endsection
