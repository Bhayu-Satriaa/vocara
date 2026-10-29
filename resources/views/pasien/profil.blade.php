@extends('layouts.app')

@section('judul', $profil->isEmpty() ? 'Lengkapi Profil' : 'Profil')
@section('crumb', $profil->isEmpty() ? 'Lengkapi Profil' : 'Profil Pasien')

@section('isi')
    @if ($profil->isEmpty())
        @if ($pengajuanMenunggu->isNotEmpty())
            {{-- Profil pertama sudah diajukan tapi masih menunggu approval admin --}}
            <div class="mx-auto max-w-xl">
                <div class="mb-6 rounded-[14px] border border-amber-soft bg-amber-soft/50 p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
                    <div class="flex items-start gap-3.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-amber-soft text-amber-ink">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-[17px] font-semibold tracking-tight text-ink">Pengajuan Profil Sedang Diproses Admin</h2>
                            <p class="mt-1 text-[13.5px] leading-relaxed text-slate-700">
                                Data profil kamu sudah berhasil dikirim ke admin klinik. Mohon menunggu persetujuan admin.
                                Setelah disetujui, <strong class="text-ink">Nomor Rekam Medis</strong> dan <strong class="text-ink">Kartu QR</strong> akan langsung diterbitkan otomatis.
                            </p>
                        </div>
                    </div>
                </div>

                @foreach ($pengajuanMenunggu as $item)
                    <div class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
                        <div class="mb-4 flex items-start justify-between gap-4 border-b border-line-soft pb-3.5">
                            <div>
                                <p class="text-[17px] font-semibold tracking-tight text-ink">{{ $item->nama_lengkap }}</p>
                                <p class="mt-0.5 text-xs text-muted">
                                    {{ $item->hubungan_keluarga }} &middot; Diajukan {{ $item->created_at->translatedFormat('d M Y, H:i') }}
                                </p>
                            </div>
                            <span class="rounded-full bg-amber-soft px-2.5 py-1 text-[11.5px] font-semibold text-amber-ink">
                                Menunggu Persetujuan
                            </span>
                        </div>

                        <dl class="grid grid-cols-1 gap-3.5 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Nomor WA</dt>
                                <dd class="mt-0.5 font-mono font-medium text-ink">{{ $item->no_wa }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Tanggal lahir</dt>
                                <dd class="mt-0.5 font-medium text-ink">{{ $item->tgl_lahir?->format('d-m-Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Jenis kelamin</dt>
                                <dd class="mt-0.5 font-medium text-ink">{{ $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Golongan darah</dt>
                                <dd class="mt-0.5 font-medium text-ink">{{ $item->golongan_darah }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Alamat</dt>
                                <dd class="mt-0.5 font-medium text-ink">{{ $item->alamat ?: '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Belum ada data diri dan belum ada pengajuan: tampilkan form Lengkapi Profil --}}
            <div class="mx-auto max-w-xl">
                <h1 class="mb-1.5 text-[30px] font-bold tracking-tight leading-tight max-[820px]:text-2xl text-ink">Lengkapi profil</h1>
                <p class="mb-6 text-sm text-muted">
                    Nomor WA <strong class="text-ink">wajib</strong> — dipakai untuk konfirmasi jadwal. Pengajuan profil akan dikirim ke admin untuk persetujuan sebelum Nomor Rekam Medis dan Kartu QR diterbitkan.
                </p>

                @if ($pengajuanRiwayat->where('status', 'ditolak')->isNotEmpty())
                    <div class="mb-5 rounded-[10px] border border-red-200 bg-red-50 p-4 text-[13px] text-red-800">
                        <p class="font-semibold">Pengajuan sebelumnya ditolak:</p>
                        @foreach ($pengajuanRiwayat->where('status', 'ditolak') as $tolak)
                            <p class="mt-1">&bull; {{ $tolak->nama_lengkap }}: {{ $tolak->catatan_admin ?: 'Data tidak sesuai' }}</p>
                        @endforeach
                        <p class="mt-2 text-slate-600">Silakan kirimkan kembali data yang benar melalui form di bawah.</p>
                    </div>
                @endif

                @include('pasien._form-profil', [
                    'pasien' => null,
                    'aksi' => route('pasien.profil.simpan'),
                    'metode' => 'PUT',
                    'tombol' => 'Kirim pengajuan ke admin',
                    'pilihUtama' => false,
                    'namaDefault' => auth()->user()->name,
                    'hubungan' => 'diri sendiri',
                ])
            </div>
        @endif
    @else
        <div class="mx-auto max-w-2xl">
            <div class="mb-6 flex items-start justify-between gap-4 max-[640px]:flex-col">
                <div>
                    <h1 class="mb-1 text-[30px] font-bold tracking-tight leading-tight max-[820px]:text-2xl text-ink">Profil</h1>
                    <p class="text-sm text-muted">
                        Ada {{ $profil->count() }} data diri di akun ini. Yang bertanda
                        <strong class="text-ink">dipakai</strong> adalah identitas pada kartu QR dan booking.
                    </p>
                </div>
                <a href="{{ route('pasien.profil.tambah') }}"
                   class="inline-flex h-[38px] shrink-0 items-center gap-2 rounded-[9px] bg-primary px-3.5 text-[13.5px] font-semibold text-white no-underline hover:bg-primary-hover">
                    Tambah data diri
                </a>
            </div>

            {{-- Bagian Pengajuan yang Sedang Menunggu Approval Admin --}}
            @if ($pengajuanMenunggu->isNotEmpty())
                <div class="mb-6 rounded-[14px] border border-amber-soft bg-amber-soft/50 p-5 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
                    <h2 class="text-sm font-semibold text-amber-ink mb-3 flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Pengajuan Menunggu Persetujuan Admin ({{ $pengajuanMenunggu->count() }})
                    </h2>
                    <div class="space-y-3">
                        @foreach ($pengajuanMenunggu as $p)
                            <div class="flex items-center justify-between gap-3 rounded-[10px] bg-surface p-3.5 border border-line-soft text-sm">
                                <div>
                                    <p class="font-semibold text-ink">{{ $p->nama_lengkap }}</p>
                                    <p class="text-xs text-muted">
                                        {{ $p->hubungan_keluarga }} &middot; No WA: <span class="font-mono">{{ $p->no_wa }}</span> &middot; Diajukan {{ $p->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-full bg-amber-soft px-2.5 py-1 text-[11.5px] font-semibold text-amber-ink">
                                    Menunggu persetujuan
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="space-y-4">
                @foreach ($profil as $item)
                    <div class="rounded-[14px] border bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)] {{ $item->is_utama ? 'border-primary ring-1 ring-primary/20' : 'border-line' }}">
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[17px] font-semibold tracking-tight text-ink">{{ $item->nama_lengkap }}</p>
                                <p class="mt-0.5 text-xs text-muted">
                                    {{ $item->hubungan_keluarga }} &middot; <span class="font-mono font-medium text-ink">{{ $item->no_rm }}</span>
                                </p>
                            </div>
                            @if ($item->is_utama)
                                <span class="shrink-0 rounded-full bg-primary-soft px-2.5 py-1 text-[11.5px] font-semibold text-primary">
                                    dipakai
                                </span>
                            @endif
                        </div>

                        <dl class="grid grid-cols-1 gap-3.5 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Nomor WA</dt>
                                <dd class="mt-0.5 font-mono text-ink">{{ $item->no_wa }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Tanggal lahir</dt>
                                <dd class="mt-0.5 text-ink">{{ $item->tgl_lahir?->format('d-m-Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Jenis kelamin</dt>
                                <dd class="mt-0.5 text-ink">{{ $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Golongan darah</dt>
                                <dd class="mt-0.5 text-ink">{{ $item->golongan_darah }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Status identitas</dt>
                                <dd class="mt-1">
                                    @if ($item->sudahTerverifikasi())
                                        <span class="rounded-full bg-green-soft px-2.5 py-1 text-[11.5px] font-semibold text-green-ink">
                                            terverifikasi
                                        </span>
                                    @else
                                        <span class="rounded-full bg-amber-soft px-2.5 py-1 text-[11.5px] font-semibold text-amber-ink">
                                            belum terverifikasi
                                        </span>
                                    @endif
                                </dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-[11px] font-semibold uppercase tracking-[.07em] text-faint">Alamat</dt>
                                <dd class="mt-0.5 text-ink">{{ $item->alamat ?: '-' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-5 flex flex-wrap items-center gap-2.5 border-t border-line-soft pt-4">
                            @if ($item->is_utama)
                                <a href="{{ route('pasien.qr') }}"
                                   class="inline-flex h-[38px] items-center gap-2 rounded-[9px] bg-primary px-3.5 text-[13.5px] font-semibold text-white no-underline hover:bg-primary-hover">
                                    Lihat kartu QR
                                </a>
                            @else
                                <form method="POST" action="{{ route('pasien.profil.pakai', $item) }}">
                                    @csrf
                                    <button type="submit"
                                            class="h-[38px] cursor-pointer rounded-[9px] bg-slate-800 px-3.5 text-[13.5px] font-semibold text-white hover:bg-slate-700">
                                        Pakai data diri ini
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('pasien.profil.ubah', $item) }}"
                               class="inline-flex h-[38px] items-center rounded-[9px] border border-line bg-surface px-3.5 text-[13.5px] font-semibold text-ink hover:bg-slate-50 no-underline">
                                Ubah
                            </a>

                            @unless ($item->sudahTerverifikasi())
                                <span class="text-xs text-faint ml-1">Menunggu verifikasi KTP oleh petugas.</span>
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mt-5 rounded-[10px] border border-dashed border-line bg-slate-50 px-3.5 py-3 text-[12.5px] text-faint">
                Setiap data diri punya nomor rekam medis dan kartu QR sendiri, jadi masing-masing orang
                tetap punya rekam medis terpisah. Verifikasi identitas dilakukan petugas dengan mencocokkan KTP.
            </p>
        </div>
    @endif
@endsection
