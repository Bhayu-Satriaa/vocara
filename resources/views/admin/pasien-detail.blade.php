@extends('layouts.app')

@section('judul', 'Detail Data Pasien')
@section('crumb', 'Data pasien')
@section('main-class', 'max-w-none px-6 xl:px-8 pt-9 pb-14 max-[1080px]:px-4 max-[1080px]:pt-7')

@section('isi')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('admin.pasien.index') }}" class="text-[13px] font-medium text-primary no-underline hover:underline">
                &larr; Kembali ke daftar pasien
            </a>
            <h1 class="mt-2 text-[24px] leading-tight font-bold tracking-tight text-ink">{{ $pasien->nama_lengkap }}</h1>
            <p class="mt-1 font-mono text-sm font-semibold text-primary">No. RM: {{ $pasien->no_rm }}</p>
        </div>

        <div class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $pasien->sudahTerverifikasi() ? 'bg-green-soft text-green-ink' : 'bg-amber-soft text-amber-ink' }}">
                    {{ $pasien->sudahTerverifikasi() ? 'Terverifikasi' : 'Belum verifikasi' }}
                </span>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700 capitalize">
                    {{ $pasien->hubungan_keluarga }}
                </span>
                @if ($pasien->is_utama)
                    <span class="rounded-full bg-primary-soft px-2.5 py-0.5 text-xs font-semibold text-primary">
                        Profil utama akun
                    </span>
                @endif
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-muted">Tanggal lahir</dt>
                    <dd class="mt-0.5 font-medium text-ink">
                        {{ $pasien->tgl_lahir?->format('d/m/Y') }} ({{ $pasien->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }})
                    </dd>
                </div>
                <div>
                    <dt class="text-muted">Nomor WhatsApp</dt>
                    <dd class="mt-0.5 font-medium text-ink">{{ $pasien->no_wa }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Golongan darah</dt>
                    <dd class="mt-0.5 font-medium text-ink">{{ $pasien->golongan_darah ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Alergi</dt>
                    <dd class="mt-0.5 font-medium text-ink">{{ $pasien->alergi ?: 'Tidak ada' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-muted">Alamat</dt>
                    <dd class="mt-0.5 font-medium text-ink">{{ $pasien->alamat ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Akun pemilik</dt>
                    <dd class="mt-0.5 font-medium text-ink">{{ $pasien->user->name ?? '-' }}</dd>
                    <dd class="text-[12.5px] text-faint">{{ $pasien->user->email ?? 'Didaftarkan walk-in oleh petugas' }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Verifikasi KTP</dt>
                    <dd class="mt-0.5 font-medium text-ink">
                        @if ($pasien->sudahTerverifikasi())
                            {{ $pasien->verifier->name ?? 'Petugas' }} &middot; {{ $pasien->verified_at?->format('d/m/Y H:i') }}
                        @else
                            Belum diverifikasi
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
