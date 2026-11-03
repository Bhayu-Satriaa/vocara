@extends('layouts.app')

@section('judul', 'Masuk Staf')
@section('main-class', 'flex min-h-screen items-center justify-center px-4 py-8')

@section('isi')
    <div class="kartu-login grid w-full max-w-[1040px] overflow-hidden rounded-[14px] border border-line bg-surface shadow-[0_18px_50px_rgba(15,23,42,.10)] lg:grid-cols-2">

        <div class="flex items-center justify-center bg-surface px-6 py-10 sm:px-10 lg:px-16">
            <div class="w-full max-w-[420px]">
                <div class="mb-10 flex items-center gap-2.5">
                    <span class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-[10px] bg-primary text-white">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 3v18M3 12h18"/></svg>
                    </span>
                    <span>
                        <span class="block text-[15px] font-bold tracking-tight text-ink">Klinik Sehat</span>
                        <span class="block text-xs text-faint">Sistem Informasi</span>
                    </span>
                </div>

                <h1 class="text-[24px] leading-tight font-bold tracking-tight text-ink">Selamat datang kembali</h1>
                <p class="mt-1 text-sm text-muted">Masuk untuk mengelola layanan klinik.</p>

                <form method="POST" action="{{ route('login.masuk') }}" class="mt-8 space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-[13px] font-semibold text-ink">
                            Email <span data-wajib="email" class="hidden text-rose-500">*</span>
                        </label>
                        <div class="relative mt-1.5">
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                                   placeholder="Masukkan alamat email"
                                   class="h-[38px] w-full rounded-lg border border-line bg-slate-50 pl-3.5 pr-10 text-sm text-ink placeholder:text-faint focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">
                            <span class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-faint">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="m3 7 9 6 9-6"/></svg>
                            </span>
                        </div>
                        @error('email')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-[13px] font-semibold text-ink">
                            Password <span data-wajib="password" class="hidden text-rose-500">*</span>
                        </label>
                        <div class="relative mt-1.5">
                            <input id="password" name="password" type="password" required
                                   placeholder="Masukkan password"
                                   class="h-[38px] w-full rounded-lg border border-line bg-slate-50 pl-3.5 pr-10 text-sm text-ink placeholder:text-faint focus:border-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary">
                            <button id="lihat-password" type="button" aria-label="Tampilkan password" aria-pressed="false"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-md p-1 text-faint hover:text-ink">
                                <svg id="ikon-mata" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg id="ikon-mata-silang" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="hidden" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c6.5 0 10 7 10 7a17.8 17.8 0 0 1-2.4 3.3M6.2 6.2A17.6 17.6 0 0 0 2 12s3.5 7 10 7a10.8 10.8 0 0 0 4.2-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <label class="flex cursor-pointer items-center gap-2 text-[13px] text-muted">
                            <input type="checkbox" name="ingat" value="1" class="h-4 w-4 accent-primary">
                            Ingat saya
                        </label>
                        <span class="text-[12.5px] text-faint">Lupa password? Hubungi admin</span>
                    </div>

                    <button type="submit"
                            class="flex h-[38px] w-full items-center justify-center rounded-[9px] bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                        Masuk
                    </button>
                </form>

                <div class="my-6 flex items-center gap-3 text-[12.5px] text-faint">
                    <span class="h-px flex-1 bg-line"></span>
                    Atau, masuk dengan
                    <span class="h-px flex-1 bg-line"></span>
                </div>

                <a href="{{ route('google.arahkan') }}"
                   class="flex h-[38px] w-full items-center justify-center gap-2.5 rounded-[9px] border border-line bg-surface text-sm font-semibold text-ink no-underline hover:bg-slate-50">
                    <svg width="17" height="17" viewBox="0 0 48 48" aria-hidden="true">
                        <path fill="#EA4335" d="M24 9.5c3.5 0 6.7 1.2 9.2 3.6l6.9-6.9C35.9 2.4 30.5 0 24 0 14.6 0 6.5 5.4 2.6 13.2l8 6.2C12.4 13.7 17.7 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M47 24.6c0-1.6-.2-3.1-.4-4.6H24v9h12.9c-.6 3-2.3 5.5-4.8 7.2l7.7 6c4.5-4.2 7.2-10.4 7.2-17.6z"/>
                        <path fill="#FBBC05" d="M10.5 28.6A14.5 14.5 0 0 1 9.8 24c0-1.6.3-3.1.8-4.6l-8-6.2A24 24 0 0 0 0 24c0 3.9.9 7.5 2.6 10.8l7.9-6.2z"/>
                        <path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.7-6c-2.2 1.5-4.9 2.3-8.2 2.3-6.3 0-11.6-4.2-13.5-9.9l-8 6.2C6.5 42.6 14.6 48 24 48z"/>
                    </svg>
                    Masuk dengan Google
                </a>

                <p class="mt-6 text-center text-[13px] text-muted">
                    Pasien baru?
                    <a href="{{ route('google.arahkan') }}" class="font-semibold text-primary no-underline hover:underline">Daftar lewat Google</a>
                </p>
            </div>
        </div>

        <div class="relative isolate hidden lg:block">
            <img src="{{ asset('images/klinik2.jpg') }}" alt="Ruang tunggu klinik"
                 class="absolute inset-0 h-full w-full object-cover"
                 style="object-position: 80% 50%;">
        </div>
    </div>

    @if (session('sukses'))
        <div id="toast-login" role="status" aria-live="polite"
             class="fixed right-4 top-4 z-50 flex max-w-[calc(100vw-2rem)] items-start gap-3 rounded-[14px] border border-green-ink/20 bg-green-soft px-4 py-3 text-sm text-green-ink shadow-[0_10px_30px_rgba(15,23,42,.15)] transition-opacity duration-300 sm:max-w-sm">
            <svg class="mt-0.5 shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m9 11 3 3L22 4"/></svg>
            <span class="leading-snug">{{ session('sukses') }}</span>
            <button type="button" onclick="this.parentElement.remove()" aria-label="Tutup notifikasi"
                    class="ml-auto shrink-0 rounded p-0.5 text-green-ink/60 hover:text-green-ink">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <script>
        document.querySelector('#lihat-password')?.addEventListener('click', (event) => {
            const input = document.querySelector('#password');
            const tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';
            event.currentTarget.setAttribute('aria-pressed', tampil ? 'true' : 'false');
            document.querySelector('#ikon-mata').classList.toggle('hidden', tampil);
            document.querySelector('#ikon-mata-silang').classList.toggle('hidden', !tampil);
        });

        document.querySelectorAll('#email, #password').forEach((el) => {
            el.addEventListener('invalid', () => {
                document.querySelector(`[data-wajib="${el.id}"]`)?.classList.remove('hidden');
            });
        });

        const toastLogin = document.querySelector('#toast-login');
        if (toastLogin) {
            setTimeout(() => {
                toastLogin.classList.add('opacity-0');
                setTimeout(() => toastLogin.remove(), 300);
            }, 3500);
        }
    </script>
@endsection
