@extends('layouts.app')

@section('judul', 'Persetujuan Profil Pasien')

@section('isi')
<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">Persetujuan Profil Pasien</h1>
            <p class="text-sm text-slate-500">
                Tempat penampungan request pendaftaran profil dari pasien. Saat disetujui (approve), data akan resmi menjadi pasien dan otomatis diterbitkan Nomor Rekam Medis serta Kartu QR.
            </p>
        </div>
        <div class="shrink-0">
            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">
                {{ $jumlahMenunggu }} menunggu persetujuan
            </span>
        </div>
    </div>

    {{-- Filter Tab --}}
    <div class="mb-6 flex border-b border-slate-200 text-sm font-medium">
        <a href="{{ route('admin.persetujuan-profil.index', ['status' => 'menunggu']) }}"
           class="border-b-2 px-4 py-2.5 transition {{ $statusFilter === 'menunggu' ? 'border-teal-600 text-teal-700 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Menunggu ({{ $jumlahMenunggu }})
        </a>
        <a href="{{ route('admin.persetujuan-profil.index', ['status' => 'disetujui']) }}"
           class="border-b-2 px-4 py-2.5 transition {{ $statusFilter === 'disetujui' ? 'border-teal-600 text-teal-700 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Disetujui
        </a>
        <a href="{{ route('admin.persetujuan-profil.index', ['status' => 'ditolak']) }}"
           class="border-b-2 px-4 py-2.5 transition {{ $statusFilter === 'ditolak' ? 'border-teal-600 text-teal-700 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Ditolak
        </a>
        <a href="{{ route('admin.persetujuan-profil.index', ['status' => 'semua']) }}"
           class="border-b-2 px-4 py-2.5 transition {{ $statusFilter === 'semua' ? 'border-teal-600 text-teal-700 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Semua Riwayat
        </a>
    </div>

    @if ($permohonan->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900">Belum ada pengajuan</h3>
            <p class="mt-1 text-xs text-slate-500">Tidak ada pengajuan profil dengan status "{{ $statusFilter }}".</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($permohonan as $item)
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-semibold text-slate-900">{{ $item->nama_lengkap }}</h3>
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                    {{ $item->hubungan_keluarga }}
                                </span>
                                @if ($item->is_utama)
                                    <span class="rounded bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 border border-teal-200">
                                        Calon profil utama
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                Diajukan oleh akun: <span class="font-medium text-slate-700">{{ $item->user->name ?? '-' }}</span> ({{ $item->user->email ?? '-' }}) &middot; {{ $item->created_at->translatedFormat('d M Y, H:i') }}
                            </p>
                        </div>

                        <div>
                            @if ($item->status === 'menunggu')
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                    Menunggu Persetujuan
                                </span>
                            @elseif ($item->status === 'disetujui')
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                    Disetujui
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                    Ditolak
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 rounded-md bg-slate-50 p-3 text-xs sm:grid-cols-4">
                        <div>
                            <span class="text-slate-500 block">Nomor WhatsApp</span>
                            <span class="font-medium text-slate-800">{{ $item->no_wa }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Tanggal Lahir</span>
                            <span class="font-medium text-slate-800">{{ $item->tgl_lahir?->format('d/m/Y') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Jenis Kelamin</span>
                            <span class="font-medium text-slate-800">{{ $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Golongan Darah</span>
                            <span class="font-medium text-slate-800">{{ $item->golongan_darah }}</span>
                        </div>
                        @if ($item->alamat)
                            <div class="col-span-2 sm:col-span-4">
                                <span class="text-slate-500 block">Alamat</span>
                                <span class="font-medium text-slate-800">{{ $item->alamat }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($item->status === 'disetujui' && $item->patient)
                        <div class="mt-3 flex items-center justify-between rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                            <div>
                                Disetujui oleh: <strong>{{ $item->approver->name ?? 'Admin' }}</strong> pada {{ $item->approved_at?->format('d/m/Y H:i') }}
                            </div>
                            <div>
                                No Rekam Medis: <strong class="font-mono">{{ $item->patient->no_rm }}</strong>
                            </div>
                        </div>
                    @elseif ($item->status === 'ditolak')
                        <div class="mt-3 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800">
                            Ditolak oleh: <strong>{{ $item->approver->name ?? 'Admin' }}</strong> &middot; Catatan: {{ $item->catatan_admin ?: 'Tanpa catatan' }}
                        </div>
                    @endif

                    @if ($item->status === 'menunggu')
                        <div class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3">
                            {{-- Form Tolak --}}
                            <form method="POST" action="{{ route('admin.persetujuan-profil.reject', $item) }}"
                                  onsubmit="return confirm('Apakah Anda yakin ingin menolak permohonan profil ini?');">
                                @csrf
                                <input type="hidden" name="catatan_admin" value="Data pengajuan tidak valid / ditolak oleh admin.">
                                <button type="submit"
                                        class="rounded-md border border-rose-300 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">
                                    Tolak Pengajuan
                                </button>
                            </form>

                            {{-- Form Setujui (Approve) --}}
                            <form method="POST" action="{{ route('admin.persetujuan-profil.approve', $item) }}"
                                  onsubmit="return confirm('Setujui pengajuan profil ini? Sistem akan otomatis menerbitkan No RM dan Kartu QR.');">
                                @csrf
                                <button type="submit"
                                        class="rounded-md bg-teal-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-teal-700 shadow-sm">
                                    Setujui & Terbitkan No RM + QR
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $permohonan->links() }}
        </div>
    @endif
</div>
@endsection

