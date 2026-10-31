{{--
    Satu data diri (pasien). Dipakai halaman Lengkapi Profil, Tambah data diri, dan
    Ubah data diri — jadi tampilannya tetap sama di semua tahap.
    Variabel: $pasien (|null), $aksi, $metode ('POST'|'PUT'), $tombol, $pilihUtama (bool),
              $namaDefault (string), $hubungan (string|null; null = wajib pilih)
--}}

@if ($pasien)
    <p class="mb-4 rounded-[10px] border border-line bg-surface px-4 py-3 text-sm text-muted shadow-[0_1px_2px_rgba(16,24,40,.05)]">
        Nomor rekam medis: <strong class="font-mono text-ink">{{ $pasien->no_rm }}</strong> &middot; kartu QR tetap memakai token yang lama.
    </p>
@endif

<form method="POST" action="{{ $aksi }}"
      class="space-y-4 rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
    @csrf
    @if ($metode === 'PUT')
        @method('PUT')
    @endif

    <div>
        <label for="nama_lengkap" class="mb-1.5 block text-[12.5px] font-semibold text-ink">Nama lengkap (sesuai KTP)</label>
        <input id="nama_lengkap" name="nama_lengkap" type="text" required
               value="{{ old('nama_lengkap', $pasien->nama_lengkap ?? $namaDefault) }}"
               class="h-10 w-full rounded-[10px] border border-line bg-surface px-3 text-sm focus:border-primary focus:outline-none">
        @error('nama_lengkap') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="hubungan_keluarga" class="mb-1.5 block text-[12.5px] font-semibold text-ink">Data diri ini milik siapa</label>
        <select id="hubungan_keluarga" name="hubungan_keluarga" required
                class="h-10 w-full rounded-[10px] border border-line bg-surface px-3 text-sm focus:border-primary focus:outline-none">
            @if ($hubungan === null)
                <option value="" disabled @selected(! old('hubungan_keluarga', $pasien?->hubungan_keluarga))>
                    -- pilih --
                </option>
            @endif
            @foreach (\App\Models\Patient::HUBUNGAN as $pilihan)
                <option value="{{ $pilihan }}"
                        @selected(old('hubungan_keluarga', $pasien->hubungan_keluarga ?? $hubungan) === $pilihan)>
                    {{ $pilihan }}
                </option>
            @endforeach
        </select>
        @error('hubungan_keluarga') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="no_wa" class="mb-1.5 block text-[12.5px] font-semibold text-ink">Nomor WA aktif</label>
        <input id="no_wa" name="no_wa" type="text" required
               value="{{ old('no_wa', $pasien->no_wa ?? '') }}"
               class="h-10 w-full rounded-[10px] border border-line bg-surface px-3 text-sm focus:border-primary focus:outline-none">
        @error('no_wa') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="tgl_lahir" class="mb-1.5 block text-[12.5px] font-semibold text-ink">Tanggal lahir</label>
            <input id="tgl_lahir" name="tgl_lahir" type="date" required
                   value="{{ old('tgl_lahir', isset($pasien) ? $pasien->tgl_lahir?->format('Y-m-d') : '') }}"
                   class="h-10 w-full rounded-[10px] border border-line bg-surface px-3 text-sm focus:border-primary focus:outline-none">
            @error('tgl_lahir') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="jenis_kelamin" class="mb-1.5 block text-[12.5px] font-semibold text-ink">Jenis kelamin</label>
            <select id="jenis_kelamin" name="jenis_kelamin" required
                    class="h-10 w-full rounded-[10px] border border-line bg-surface px-3 text-sm focus:border-primary focus:outline-none">
                <option value="L" @selected(old('jenis_kelamin', $pasien->jenis_kelamin ?? '') === 'L')>Laki-laki</option>
                <option value="P" @selected(old('jenis_kelamin', $pasien->jenis_kelamin ?? '') === 'P')>Perempuan</option>
            </select>
            @error('jenis_kelamin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="golongan_darah" class="mb-1.5 block text-[12.5px] font-semibold text-ink">Golongan darah (opsional)</label>
        <select id="golongan_darah" name="golongan_darah"
                class="h-10 w-full rounded-[10px] border border-line bg-surface px-3 text-sm focus:border-primary focus:outline-none">
            @foreach (['-' => 'belum tahu', 'A' => 'A', 'B' => 'B', 'AB' => 'AB', 'O' => 'O'] as $nilai => $label)
                <option value="{{ $nilai }}" @selected(old('golongan_darah', $pasien->golongan_darah ?? '-') === $nilai)>{{ $label }}</option>
            @endforeach
        </select>
        @error('golongan_darah') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="alamat" class="mb-1.5 block text-[12.5px] font-semibold text-ink">Alamat (opsional)</label>
        <textarea id="alamat" name="alamat" rows="3"
                  class="w-full rounded-[10px] border border-line bg-surface p-3 text-sm focus:border-primary focus:outline-none">{{ old('alamat', $pasien->alamat ?? '') }}</textarea>
        @error('alamat') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @if ($pilihUtama)
        <label class="flex items-start gap-3 rounded-[10px] border border-line bg-slate-50 px-3.5 py-3 text-sm">
            <input type="checkbox" name="is_utama" value="1" class="mt-0.5 h-4 w-4 accent-primary"
                   @checked(old('is_utama', false))>
            <span class="text-muted text-[13px]">
                Langsung jadikan data diri ini yang dipakai untuk kartu QR dan booking.
            </span>
        </label>
    @endif

    <button type="submit"
            class="inline-flex h-[38px] cursor-pointer items-center justify-center gap-2 rounded-[9px] bg-primary px-4 text-[13.5px] font-semibold text-white no-underline hover:bg-primary-hover">
        {{ $tombol }}
    </button>
</form>
