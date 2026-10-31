<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Sistem Klinik Kesehatan')</title>
    {{-- Terapkan state sidebar dari localStorage sebelum paint, supaya tidak kedip. --}}
    <script>if (localStorage.getItem('vocara.sidebar') === 'collapsed') document.documentElement.dataset.collapsed = '';</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
    @auth
        @php
            $inisial = collect(explode(' ', auth()->user()->name))
                ->filter()->map(fn ($kata) => mb_substr($kata, 0, 1))->take(2)->implode('');
            $menu = [
                ['label' => 'Dashboard', 'href' => route('dashboard'), 'ikon' => 'grid', 'aktif' => request()->routeIs('dashboard')],
            ];
            if (auth()->user()->punyaRole('petugas', 'admin')) {
                $menu[] = ['label' => 'Scan QR pasien', 'href' => route('petugas.scan'), 'ikon' => 'scan', 'aktif' => request()->routeIs('petugas.scan*')];
            }
            if (auth()->user()->punyaRole('admin')) {
                $menu[] = ['label' => 'Panel admin', 'href' => url('/admin'), 'ikon' => 'panel', 'aktif' => request()->is('admin*')];
                if (\Illuminate\Support\Facades\Route::has('admin.persetujuan-profil.index')) {
                    $menu[] = ['label' => 'Persetujuan Profil', 'href' => route('admin.persetujuan-profil.index'), 'ikon' => 'user', 'aktif' => request()->routeIs('admin.persetujuan-profil*')];
                }
            }
            if (auth()->user()->punyaRole('pasien')) {
                $menu[] = ['label' => 'Profil', 'href' => route('pasien.profil'), 'ikon' => 'user', 'aktif' => request()->routeIs('pasien.profil*')];
                $menu[] = ['label' => 'Kartu QR', 'href' => route('pasien.qr'), 'ikon' => 'kartu', 'aktif' => request()->routeIs('pasien.qr')];
                $menu[] = ['label' => 'Tanya chatbot', 'href' => route('pasien.chat'), 'ikon' => 'chat', 'aktif' => request()->routeIs('pasien.chat*')];
            }
        @endphp

        <aside class="sidebar fixed inset-y-0 left-0 z-20 flex w-[264px] flex-col overflow-hidden bg-surface px-4 pb-5 pt-6 max-[820px]:hidden">
            <div class="brand flex items-center gap-2.5 px-2">
                <span class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-[10px] bg-primary text-white">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 3v18M3 12h18"/></svg>
                </span>
                <span class="hide-collapsed">
                    <span class="block text-[15px] font-bold tracking-tight">Klinik Sehat</span>
                    <span class="block text-xs text-faint">Sistem Informasi</span>
                </span>
            </div>

            <p class="hide-collapsed nav-section px-2.5 pt-6 pb-2 text-[11px] font-semibold tracking-[.07em] text-faint uppercase">Menu</p>

            <nav class="flex flex-col gap-0.5">
                @foreach ($menu as $item)
                    <a href="{{ $item['href'] }}" data-label="{{ $item['label'] }}"
                        @if ($item['aktif']) aria-current="page" @endif
                        class="nav-item flex h-10 items-center gap-2.5 rounded-[10px] px-2.5 text-sm no-underline {{ $item['aktif'] ? 'bg-primary-soft font-semibold text-primary' : 'text-muted hover:bg-slate-50 hover:text-ink' }}">
                        @switch($item['ikon'])
                            @case('grid')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                            @break
                            @case('scan')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M3 12h18"/></svg>
                            @break
                            @case('panel')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg>
                            @break
                            @case('user')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            @break
                            @case('kartu')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                            @break
                            @case('chat')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-8 8H8l-5 3 1.4-4.2A8 8 0 1 1 21 12z"/></svg>
                            @break
                        @endswitch
                        <span class="hide-collapsed">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="mt-auto border-t border-line px-2 pt-3.5">
                <div class="side-user flex items-center gap-2.5">
                    <span class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary">{{ $inisial }}</span>
                    <span class="hide-collapsed leading-tight">
                        <span class="block text-[13px] font-semibold">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-muted">{{ auth()->user()->role }}</span>
                    </span>
                </div>
            </div>
        </aside>
    @endauth

    <div class="main min-h-screen @auth ml-[264px] max-[820px]:ml-0 @endauth">
        <header class="sticky top-0 z-10 flex h-16 items-center justify-between gap-4 border-b border-line bg-slate-50 pl-4 pr-10 max-[820px]:pr-6">
            <div class="flex min-w-0 items-center gap-3.5">
                @auth
                    <button id="toggle-sidebar" type="button" title="Ciutkan / lebarkan sidebar"
                            class="flex h-[38px] w-[38px] shrink-0 cursor-pointer items-center justify-center rounded-[9px] border border-line bg-slate-50 font-semibold text-ink hover:bg-slate-100">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg>
                    </button>
                @endauth
                <p class="truncate text-[13px] text-faint">@yield('crumb', 'Sistem Klinik Kesehatan')</p>
            </div>

            <div class="flex items-center gap-3.5">
                @auth
                    <span class="text-right leading-tight">
                        <span class="block text-[13px] font-semibold">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-muted">{{ auth()->user()->role }}</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="h-[38px] cursor-pointer rounded-[9px] border border-line bg-slate-50 px-3.5 text-[13.5px] font-semibold text-ink hover:bg-slate-100">
                            Keluar
                        </button>
                    </form>
                @endauth
            </div>
        </header>

        <main class="mx-auto max-w-[1180px] px-10 pt-9 pb-14 max-[1080px]:px-6 max-[1080px]:pt-7">
            @if (session('sukses'))
                <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('sukses') }}
                </div>
            @endif

            @if (session('peringatan'))
                <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ session('peringatan') }}
                </div>
            @endif

            @yield('isi')
        </main>
    </div>
</body>
</html>
