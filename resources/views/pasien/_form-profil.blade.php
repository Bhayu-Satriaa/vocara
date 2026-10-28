{{--
    Satu data diri (pasien). Dipakai halaman Lengkapi Profil, Tambah data diri, dan
    Ubah data diri — jadi tampilannya tetap sama di semua tahap.
    Variabel: $pasien (|null), $aksi, $metode ('POST'|'PUT'), $tombol, $pilihUtama (bool),
              $namaDefault (string), $hubungan (string|null; null = wajib pilih)
--}}

@if ($pasien)
    <p class="mb-4 rounded-md border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
        Nomor rekam medis: <strong>{{ $pasien->no_rm }}</strong> &middot; kartu QR tetap memakai token yang lama.
    </p>
@endif

<form method="POST" action="{{ $aksi }}"
      class="space-y-4 rounded-lg border border-slate-200 bg-white p-6">
    @csrf
    @if ($metode === 'PUT')
        @method('PUT')
    @endif

    <div>
        <label for="nama_lengkap" class="mb-1 block text-sm font-medium">Nama lengkap (sesuai KTP)</label>
        <input id="nama_lengkap" name="nama_lengkap" type="text" required
               value="{{ old('nama_lengkap', $pasien->nama_lengkap ?? $namaDefault) }}"
               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('nama_lengkap') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="hubungan_keluarga" class="mb-1 block text-sm font-medium">Data diri ini milik siapa</label>
        <select id="hubungan_keluarga" name="hubungan_keluarga" required
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
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
        <label for="no_wa" class="mb-1 block text-sm font-medium">Nomor WA aktif</label>
        <input id="no_wa" name="no_wa" type="text" required
               value="{{ old('no_wa', $pasien->no_wa ?? '') }}"
               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('no_wa') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="tgl_lahir" class="mb-1 block text-sm font-medium">Tanggal lahir</label>
            <input id="tgl_lahir" name="tgl_lahir" type="date" required
                   value="{{ old('tgl_lahir', isset($pasien) ? $pasien->tgl_lahir?->format('Y-m-d') : '') }}"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('tgl_lahir') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="jenis_kelamin" class="mb-1 block text-sm font-medium">Jenis kelamin</label>
            <select id="jenis_kelamin" name="jenis_kelamin" required
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="L" @selected(old('jenis_kelamin', $pasien->jenis_kelamin ?? '') === 'L')>Laki-laki</option>
                <option value="P" @selected(old('jenis_kelamin', $pasien->jenis_kelamin ?? '') === 'P')>Perempuan</option>
            </select>
            @error('jenis_kelamin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="golongan_darah" class="mb-1 block text-sm font-medium">Golongan darah (opsional)</label>
        <select id="golongan_darah" name="golongan_darah"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach (['-' => 'belum tahu', 'A' => 'A', 'B' => 'B', 'AB' => 'AB', 'O' => 'O'] as $nilai => $label)
                <option value="{{ $nilai }}" @selected(old('golongan_darah', $pasien->golongan_darah ?? '-') === $nilai)>{{ $label }}</option>
            @endforeach
        </select>
        @error('golongan_darah') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="alamat" class="mb-1 block text-sm font-medium">Alamat (opsional)</label>
        <textarea id="alamat" name="alamat" rows="3"
                  class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('alamat', $pasien->alamat ?? '') }}</textarea>
        @error('alamat') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @if ($pilihUtama)
        <label class="flex items-start gap-3 rounded-md border border-slate-200 px-3 py-3 text-sm">
            <input type="checkbox" name="is_utama" value="1" class="mt-0.5 h-4 w-4 accent-teal-600"
                   @checked(old('is_utama', false))>
            <span class="text-slate-600">
                Langsung jadikan data diri ini yang dipakai untuk kartu QR dan booking.
            </span>
        </label>
    @endif

    <button type="submit"
            class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
        {{ $tombol }}
    </button>
</form>
