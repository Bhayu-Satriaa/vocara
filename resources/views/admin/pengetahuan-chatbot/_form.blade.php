@php
    $isEdit = $item->exists;
    $action = $isEdit ? route('admin.pengetahuan-chatbot.update', $item) : route('admin.pengetahuan-chatbot.store');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div>
        <label for="kategori" class="block text-xs font-semibold uppercase tracking-wider text-muted">Kategori</label>
        <input id="kategori" name="kategori" type="text" value="{{ old('kategori', $item->kategori) }}" required maxlength="50"
               placeholder="Mis. Pendaftaran, Kartu QR, Layanan, Biaya"
               class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">
        @error('kategori')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="pertanyaan" class="block text-xs font-semibold uppercase tracking-wider text-muted">Pertanyaan pasien</label>
        <input id="pertanyaan" name="pertanyaan" type="text" value="{{ old('pertanyaan', $item->pertanyaan) }}" required maxlength="255"
               placeholder='Mis. "syarat daftar berobat apa saja"'
               class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">
        @error('pertanyaan')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="jawaban" class="block text-xs font-semibold uppercase tracking-wider text-muted">Jawaban</label>
        <textarea id="jawaban" name="jawaban" rows="4" required
                  class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">{{ old('jawaban', $item->jawaban) }}</textarea>
        @error('jawaban')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <label class="flex cursor-pointer items-center gap-2 text-sm text-ink">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="h-4 w-4 accent-primary">
        Dipakai chatbot
    </label>

    <div class="flex items-center justify-end gap-3 pt-3">
        <a href="{{ route('admin.pengetahuan-chatbot.index') }}"
           class="inline-flex h-[38px] items-center justify-center rounded-[9px] border border-line bg-surface px-4 text-sm font-semibold text-ink no-underline hover:bg-slate-50">
            Batal
        </a>
        <button type="submit"
                class="inline-flex h-[38px] items-center justify-center rounded-[9px] bg-primary px-5 text-sm font-semibold text-white hover:bg-primary-hover">
            {{ $isEdit ? 'Simpan perubahan' : 'Tambah' }}
        </button>
    </div>
</form>
