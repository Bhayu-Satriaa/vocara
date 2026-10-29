@extends('layouts.app')

@section('judul', $profil->isEmpty() ? 'Lengkapi Profil' : 'Profil')

@section('isi')
    @if ($profil->isEmpty())
        {{-- Belum ada data diri: halaman ini masih jadi langkah "Lengkapi Profil". --}}
        <div class="mx-auto max-w-xl">
            <h1 class="mb-1 text-xl font-semibold">Lengkapi profil</h1>
            <p class="mb-6 text-sm text-slate-500">
                Nomor WA <strong>wajib</strong> — dipakai untuk konfirmasi jadwal. Nama, tanggal lahir, dan
                alamat dicocokkan petugas dengan KTP saat verifikasi.
            </p>

            @include('pasien._form-profil', [
                'pasien' => null,
                'aksi' => route('pasien.profil.simpan'),
                'metode' => 'PUT',
                'tombol' => 'Simpan profil',
                'pilihUtama' => false,
                'namaDefault' => auth()->user()->name,
                'hubungan' => 'diri sendiri',
            ])
        </div>
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
