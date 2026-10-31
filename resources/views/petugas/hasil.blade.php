@extends('layouts.app')

@section('judul', 'Data Pasien Hasil Scan')
@section('crumb', 'Hasil Scan QR & Check-in')

@section('isi')
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink">Hasil Scan Pasien</h1>
                <p class="text-sm text-muted">Data pasien teridentifikasi dari Token QR.</p>
            </div>
            <a href="{{ route('petugas.scan') }}" class="text-sm font-semibold text-primary hover:underline">
                &larr; Scan Pasien Lain
            </a>
        </div>

        {{-- 1. Kartu Biodata Pasien --}}
        <div class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-line pb-4 mb-5">
                <div>
                    <span class="font-mono text-xs font-bold text-primary">{{ $pasien->no_rm }}</span>
                    <h2 class="text-xl font-bold text-ink">{{ $pasien->nama_lengkap }}</h2>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $pasien->sudahTerverifikasi() ? 'bg-green-soft text-green-ink' : 'bg-amber-soft text-amber-ink' }}">
                        {{ $pasien->sudahTerverifikasi() ? '✓ Identitas KTP Terverifikasi' : '⚠️ Belum Verifikasi KTP' }}
                    </span>
                </div>
            </div>

            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-muted">Tanggal Lahir</dt>
                    <dd class="font-medium text-ink">{{ $pasien->tgl_lahir?->format('d-m-Y') }} ({{ $pasien->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }})</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted">Nomor WhatsApp</dt>
                    <dd class="font-medium text-ink">{{ $pasien->no_wa }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted">Golongan Darah</dt>
                    <dd class="font-medium text-ink">{{ $pasien->golongan_darah ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted">Riwayat Alergi</dt>
                    <dd class="font-medium text-ink">{{ $pasien->alergi ?: 'Tidak ada' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-muted">Alamat</dt>
                    <dd class="font-medium text-ink">{{ $pasien->alamat ?: '-' }}</dd>
                </div>
            </dl>

            @unless ($pasien->sudahTerverifikasi())
                <form method="POST" action="{{ route('petugas.scan.verifikasi', $pasien) }}" class="mt-5 border-t border-line pt-4">
                    @csrf
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50/60 p-3.5">
                        <p class="text-xs text-amber-900">
                            Cocokkan nama, tanggal lahir, dan alamat di atas dengan KTP fisik pasien.
                        </p>
                        <button type="submit" class="shrink-0 rounded-lg bg-emerald-700 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-600">
                            KTP Sesuai — Tandai Terverifikasi
                        </button>
                    </div>
                </form>
            @endunless
        </div>

        {{-- 2. Modul Check-in Antrean (Tugas 2) --}}
        <div class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <h2 class="text-lg font-bold text-ink mb-1">Status Kunjungan & Antrean Hari Ini</h2>

            @if ($kunjunganHariIni && in_array($kunjunganHariIni->status, ['menunggu', 'diperiksa', 'farmasi', 'pembayaran', 'selesai']))
                {{-- Sudah Check-in --}}
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/70 p-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <span class="rounded bg-emerald-600 px-2 py-0.5 text-xs font-bold text-white uppercase">
                                Sudah Check-in
                            </span>
                            <h3 class="mt-2 text-xl font-bold text-emerald-950">
                                Nomor Antrean: #{{ $kunjunganHariIni->no_antrian }}
                            </h3>
                            <p class="text-xs text-emerald-800 mt-1">
                                Poli: <strong>{{ $kunjunganHariIni->poli?->nama }}</strong> &middot;
                                Dokter: <strong>{{ $kunjunganHariIni->doctor?->user?->name ?? 'Dokter' }}</strong>
                            </p>
                            <p class="text-xs text-emerald-700 mt-0.5">
                                Waktu Check-in: {{ $kunjunganHariIni->checkin_at?->format('H:i') }} WIB (Metode: {{ strtoupper($kunjunganHariIni->checkin_metode) }})
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block rounded-full bg-emerald-100 px-3 py-1 font-mono text-sm font-bold text-emerald-800">
                                Status: {{ ucfirst($kunjunganHariIni->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            @elseif ($kunjunganHariIni && $kunjunganHariIni->status === 'booking')
                {{-- Ada Booking Online Hari Ini --}}
                <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50/70 p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <span class="rounded bg-blue-600 px-2 py-0.5 text-xs font-bold text-white uppercase">
                                Ada Booking Hari Ini
                            </span>
                            <h3 class="mt-2 text-lg font-bold text-blue-950">
                                {{ $kunjunganHariIni->poli?->nama }} &mdash; {{ $kunjunganHariIni->doctor?->user?->name ?? 'Dokter' }}
                            </h3>
                            <p class="text-xs text-blue-800 mt-1">
                                Jadwal Slot: <strong>{{ substr($kunjunganHariIni->jam, 0, 5) }} WIB</strong>
                                @if ($kunjunganHariIni->keluhan_awal)
                                    &middot; Keluhan: <em>"{{ $kunjunganHariIni->keluhan_awal }}"</em>
                                @endif
                            </p>
                        </div>

                        <form method="POST" action="{{ route('petugas.scan.checkin', $pasien) }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                                Check-in Pasien Sekarang &rarr;
                            </button>
                        </form>
                    </div>
                </div>
            @else
                {{-- Walk-in: Belum Ada Booking --}}
                <div class="mt-4">
                    <p class="text-xs text-muted mb-4">
                        Pasien belum memiliki jadwal kunjungan hari ini. Pilih Poli dan Dokter untuk mendaftarkannya langsung ke antrean hari ini:
                    </p>

                    <form method="POST" action="{{ route('petugas.scan.checkin', $pasien) }}" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="poli_id" class="block text-xs font-semibold text-muted uppercase">Poli Tujuan *</label>
                                <select id="poli_id" name="poli_id" required
                                        class="mt-1 block w-full rounded-lg border border-line bg-slate-50 px-3 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none">
                                    <option value="">-- Pilih Poli --</option>
                                    @foreach ($polis as $poli)
                                        <option value="{{ $poli->id }}">{{ $poli->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="doctor_id" class="block text-xs font-semibold text-muted uppercase">Dokter Pemeriksa *</label>
                                <select id="doctor_id" name="doctor_id" required
                                        class="mt-1 block w-full rounded-lg border border-line bg-slate-50 px-3 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none">
                                    <option value="">-- Pilih Dokter --</option>
                                    @foreach ($polis as $poli)
                                        @foreach ($poli->doctors as $doctor)
                                            <option value="{{ $doctor->id }}" data-poli="{{ $poli->id }}">
                                                {{ $doctor->user?->name ?? 'Dokter' }} ({{ $poli->nama }})
                                            </option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="keluhan_awal" class="block text-xs font-semibold text-muted uppercase">Keluhan Awal (Opsional)</label>
                            <input type="text" id="keluhan_awal" name="keluhan_awal"
                                   placeholder="Contoh: Demam sejak kemarin, batuk pilek"
                                   class="mt-1 block w-full rounded-lg border border-line bg-slate-50 px-3 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none">
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                    class="inline-flex h-[38px] items-center justify-center rounded-[9px] bg-primary px-5 text-sm font-semibold text-white hover:bg-primary-hover">
                                Masukkan ke Antrean Hari Ini
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        {{-- 3. Riwayat Scan Terakhir --}}
        <div class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <h2 class="text-sm font-bold text-ink mb-3">Riwayat Pemindaian QR</h2>
            <div class="divide-y divide-line-soft">
                @forelse ($riwayatScan as $scan)
                    <div class="flex items-center justify-between py-2 text-xs">
                        <div>
                            <span class="font-semibold text-slate-700 capitalize">{{ $scan->konteks }}</span>
                            <span class="text-muted">oleh {{ $scan->petugas?->name ?? 'Petugas' }}</span>
                        </div>
                        <span class="text-faint">{{ $scan->created_at?->format('d-m-Y H:i') }}</span>
                    </div>
                @empty
                    <p class="text-xs text-muted">Belum ada riwayat pemindaian untuk pasien ini.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
