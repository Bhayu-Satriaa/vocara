@extends('layouts.app')

@section('judul', $profil->isEmpty() ? 'Lengkapi Profil' : 'Profil')

@section('isi')
    @if ($profil->isEmpty())
        @if ($pengajuanMenunggu->isNotEmpty())
            {{-- Profil pertama sudah diajukan tapi masih menunggu approval admin --}}
            <div class="mx-auto max-w-xl">
                <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-6 text-amber-900 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="h-6 w-6 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h2 class="text-base font-semibold">Pengajuan Profil Sedang Diproses Admin</h2>
                            <p class="mt-1 text-sm text-amber-800">
                                Data profil kamu sudah berhasil dikirim ke admin klinik. Mohon menunggu persetujuan admin.
                                Setelah disetujui, <strong>Nomor Rekam Medis</strong> dan <strong>Kartu QR</strong> akan langsung diterbitkan otomatis.
                            </p>
                        </div>
                    </div>
                </div>

                @foreach ($pengajuanMenunggu as $item)
                    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                            <div>
                                <p class="text-lg font-semibold text-slate-800">{{ $item->nama_lengkap }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $item->hubungan_keluarga }} &middot; Diajukan {{ $item->created_at->translatedFormat('d M Y, H:i') }}
                                </p>
                            </div>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">
                                Menunggu Persetujuan
                            </span>
                        </div>

                        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-slate-500 text-xs">Nomor WA</dt>
                                <dd class="font-medium">{{ $item->no_wa }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500 text-xs">Tanggal lahir</dt>
                                <dd class="font-medium">{{ $item->tgl_lahir?->format('d-m-Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500 text-xs">Jenis kelamin</dt>
                                <dd class="font-medium">{{ $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500 text-xs">Golongan darah</dt>
                                <dd class="font-medium">{{ $item->golongan_darah }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-slate-500 text-xs">Alamat</dt>
                                <dd class="font-medium">{{ $item->alamat ?: '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Belum ada data diri dan belum ada pengajuan: tampilkan form Lengkapi Profil --}}
            <div class="mx-auto max-w-xl">
                <h1 class="mb-1 text-xl font-semibold">Lengkapi profil</h1>
                <p class="mb-6 text-sm text-slate-500">
                    Nomor WA <strong>wajib</strong> — dipakai untuk konfirmasi jadwal. Pengajuan profil akan dikirim ke admin untuk persetujuan sebelum Nomor Rekam Medis dan Kartu QR diterbitkan.
                </p>

                @if ($pengajuanRiwayat->where('status', 'ditolak')->isNotEmpty())
                    <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800">
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
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h1 class="mb-1 text-xl font-semibold">Profil</h1>
                    <p class="text-sm text-slate-500">
                        Ada {{ $profil->count() }} data diri di akun ini. Yang bertanda
                        <strong>dipakai</strong> adalah identitas pada kartu QR dan booking.
                    </p>
                </div>
                <a href="{{ route('pasien.profil.tambah') }}"
                   class="shrink-0 rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    Tambah data diri
                </a>
            </div>

            {{-- Bagian Pengajuan yang Sedang Menunggu Approval Admin --}}
            @if ($pengajuanMenunggu->isNotEmpty())
                <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50/70 p-4">
                    <h2 class="text-sm font-semibold text-amber-900 mb-2 flex items-center gap-2">
                        <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Pengajuan Menunggu Persetujuan Admin ({{ $pengajuanMenunggu->count() }})
                    </h2>
                    <div class="space-y-3">
                        @foreach ($pengajuanMenunggu as $p)
                            <div class="flex items-center justify-between rounded-md bg-white p-3 border border-amber-200 text-sm">
                                <div>
                                    <p class="font-semibold text-slate-800">{{ $p->nama_lengkap }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $p->hubungan_keluarga }} &middot; No WA: {{ $p->no_wa }} &middot; Diajukan {{ $p->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <span class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">
                                    Menunggu persetujuan
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($profil as $item)
                <div class="mb-4 rounded-lg border bg-white p-6 {{ $item->is_utama ? 'border-teal-300' : 'border-slate-200' }}">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold">{{ $item->nama_lengkap }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $item->hubungan_keluarga }} &middot; {{ $item->no_rm }}
                            </p>
                        </div>
                        @if ($item->is_utama)
                            <span class="shrink-0 rounded-md border border-teal-200 bg-teal-50 px-2 py-1 text-xs text-teal-800">
                                dipakai
                            </span>
                        @endif
                    </div>

                    <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-slate-500">Nomor WA</dt>
                            <dd>{{ $item->no_wa }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Tanggal lahir</dt>
                            <dd>{{ $item->tgl_lahir?->format('d-m-Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Jenis kelamin</dt>
                            <dd>{{ $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Golongan darah</dt>
                            <dd>{{ $item->golongan_darah }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Status identitas</dt>
                            <dd class="font-medium">
                                {{ $item->sudahTerverifikasi() ? 'terverifikasi' : 'belum terverifikasi' }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-slate-500">Alamat</dt>
                            <dd>{{ $item->alamat ?: '-' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-200 pt-4">
                        @if ($item->is_utama)
                            <a href="{{ route('pasien.qr') }}"
                               class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                                Lihat kartu QR
                            </a>
                        @else
                            <form method="POST" action="{{ route('pasien.profil.pakai', $item) }}">
                                @csrf
                                <button type="submit"
                                        class="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-700">
                                    Pakai data diri ini
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('pasien.profil.ubah', $item) }}"
                           class="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Ubah</a>

                        @unless ($item->sudahTerverifikasi())
                            <span class="text-xs text-slate-400">Menunggu verifikasi KTP oleh petugas.</span>
                        @endunless
                    </div>
                </div>
            @endforeach

            <p class="text-xs text-slate-400">
                Setiap data diri punya nomor rekam medis dan kartu QR sendiri, jadi masing-masing orang
                tetap punya rekam medis terpisah. Verifikasi identitas dilakukan petugas dengan mencocokkan KTP.
            </p>
        </div>
    @endif
@endsection
