@php
    $isEdit = $user->exists;
    $action = $isEdit ? route('admin.akun-staf.update', $user) : route('admin.akun-staf.store');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div>
        <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-muted">Nama</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
               class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">
        @error('name')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-muted">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
               class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">
        @error('email')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="role" class="block text-xs font-semibold uppercase tracking-wider text-muted">Role</label>
        <select id="role" name="role" required
                class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">
            @foreach (\App\Models\User::ROLE_STAF as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ ucfirst($role) }}</option>
            @endforeach
        </select>
        @error('role')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-muted">
                Password {{ $isEdit ? '(opsional)' : '' }}
            </label>
            <input id="password" name="password" type="password" @required(! $isEdit)
                   class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">
            @error('password')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-muted">Ulangi password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" @required(! $isEdit)
                   class="mt-1.5 block w-full rounded-lg border border-line bg-slate-50 px-3.5 py-2 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
    </div>
    <p class="text-xs text-faint">Minimal 8 karakter. Kosongkan kalau tidak ingin mengganti password.</p>

    <label class="flex cursor-pointer items-center gap-2 text-sm text-ink">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="h-4 w-4 accent-primary">
        Aktif
    </label>

    <div class="flex items-center justify-end gap-3 pt-3">
        <a href="{{ route('admin.akun-staf.index') }}"
           class="inline-flex h-[38px] items-center justify-center rounded-[9px] border border-line bg-surface px-4 text-sm font-semibold text-ink no-underline hover:bg-slate-50">
            Batal
        </a>
        <button type="submit"
                class="inline-flex h-[38px] items-center justify-center rounded-[9px] bg-primary px-5 text-sm font-semibold text-white hover:bg-primary-hover">
            {{ $isEdit ? 'Simpan perubahan' : 'Buat akun' }}
        </button>
    </div>
</form>
