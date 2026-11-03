@extends('layouts.app')

@section('judul', 'Pendaftaran Pasien Walk-in')
@section('crumb', 'Pendaftaran Pasien Walk-in Baru')

@section('isi')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink">Pendaftaran Pasien Walk-in</h1>
                <p class="text-sm text-muted">Daftarkan pasien yang datang langsung ke klinik tanpa akun online.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm text-muted hover:text-ink">
                &larr; Kembali
            </a>
        </div>

        <div class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            <form method="POST" action="{{ route('petugas.pasien.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="nama_lengkap" class="block text-xs font-semibold uppercase tracking-wider text-muted">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap') }}" required
                           class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary"
                           placeholder="Contoh: Budi Santoso">
                    @error('nama_lengkap')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="tgl_lahir" class="block text-xs font-semibold uppercase tracking-wider text-muted">
                            Tanggal Lahir <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="tgl_lahir" name="tgl_lahir" value="{{ old('tgl_lahir') }}" required
                               class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">
                        @error('tgl_lahir')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="jenis_kelamin" class="block text-xs font-semibold uppercase tracking-wider text-muted">
                            Jenis Kelamin <span class="text-rose-500">*</span>
                        </label>
                        <select id="jenis_kelamin" name="jenis_kelamin" required
                                class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="L" {{ old('jenis_kelamin') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ old('jenis_kelamin') === 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                        @error('jenis_kelamin')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="no_wa" class="block text-xs font-semibold uppercase tracking-wider text-muted">
                            No. WhatsApp / HP <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="no_wa" name="no_wa" value="{{ old('no_wa') }}" required
                               class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary"
                               placeholder="Contoh: 081234567890">
                        @error('no_wa')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="golongan_darah" class="block text-xs font-semibold uppercase tracking-wider text-muted">
                            Golongan Darah
                        </label>
                        <select id="golongan_darah" name="golongan_darah"
                                class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">
                            <option value="-" {{ old('golongan_darah', '-') === '-' ? 'selected' : '' }}>Tidak Tahu (-)</option>
                            <option value="A" {{ old('golongan_darah') === 'A' ? 'selected' : '' }}>A</option>
                            <option value="B" {{ old('golongan_darah') === 'B' ? 'selected' : '' }}>B</option>
                            <option value="AB" {{ old('golongan_darah') === 'AB' ? 'selected' : '' }}>AB</option>
                            <option value="O" {{ old('golongan_darah') === 'O' ? 'selected' : '' }}>O</option>
                        </select>
                        @error('golongan_darah')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="alamat" class="block text-xs font-semibold uppercase tracking-wider text-muted">
                        Alamat Lengkap (Sesuai KTP)
                    </label>
                    <textarea id="alamat" name="alamat" rows="2"
                              class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary"
                              placeholder="Alamat domisili / tempat tinggal pasien">{{ old('alamat') }}</textarea>
                    @error('alamat')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="alergi" class="block text-xs font-semibold uppercase tracking-wider text-muted">
                        Riwayat Alergi Obat / Makanan (Opsional)
                    </label>
                    <input type="text" id="alergi" name="alergi" value="{{ old('alergi') }}"
                           class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary"
                           placeholder="Kosongkan jika tidak ada">
                    @error('alergi')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-lg border border-line-soft bg-slate-50 p-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="verifikasi_langsung" value="1" checked
                               class="mt-0.5 h-4 w-4 rounded border-line text-primary focus:ring-primary">
                        <div>
                            <span class="text-sm font-semibold text-ink">Verifikasi Identitas Fisik KTP</span>
                            <p class="text-xs text-muted">
                                Centang jika Anda sudah mencocokkan nama, tanggal lahir, dan alamat pasien dengan KTP fisik yang dibawa saat pendaftaran.
                            </p>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <a href="{{ route('dashboard') }}"
                       class="h-[38px] rounded-[9px] border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex h-[38px] items-center justify-center rounded-[9px] bg-primary px-5 text-sm font-semibold text-white hover:bg-primary-hover">
                        Daftarkan & Buat Kartu QR
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

