<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                ['label' => 'Dashboard', 'href' => route('dashboard'), 'ikon' => 'grid', 'aktif' => request()->routeIs('dashboard'), 'grup' => 'Utama'],
            ];
            if (auth()->user()->punyaRole('admin')) {
                $menu[] = ['label' => 'Data pasien', 'href' => route('admin.pasien.index'), 'ikon' => 'user', 'aktif' => request()->routeIs('admin.pasien*'), 'grup' => 'Pendaftaran'];
            }
            if (auth()->user()->punyaRole('petugas', 'admin')) {
                $menu[] = ['label' => 'Pasien Baru', 'href' => route('petugas.pasien.create'), 'ikon' => 'user-plus', 'aktif' => request()->routeIs('petugas.pasien*'), 'grup' => 'Pendaftaran'];
                $menu[] = ['label' => 'Scan QR pasien', 'href' => route('petugas.scan'), 'ikon' => 'scan', 'aktif' => request()->routeIs('petugas.scan*'), 'grup' => 'Pendaftaran'];
                $menu[] = ['label' => 'Antrean hari ini', 'href' => route('petugas.antrean.index'), 'ikon' => 'antrean', 'aktif' => request()->routeIs('petugas.antrean*'), 'grup' => 'Pendaftaran'];
            }
            if (auth()->user()->punyaRole('admin')) {
                $menu[] = ['label' => 'Persetujuan profil', 'href' => route('admin.persetujuan-profil.index'), 'ikon' => 'user', 'aktif' => request()->routeIs('admin.persetujuan-profil*'), 'grup' => 'Kelola'];
                $menu[] = ['label' => 'Jadwal dokter', 'href' => route('admin.jadwal-dokter.index'), 'ikon' => 'kalender', 'aktif' => request()->routeIs('admin.jadwal-dokter*'), 'grup' => 'Kelola'];
                $menu[] = ['label' => 'Akun staf', 'href' => route('admin.akun-staf.index'), 'ikon' => 'user-plus', 'aktif' => request()->routeIs('admin.akun-staf*'), 'grup' => 'Kelola'];
                $menu[] = ['label' => 'Pengetahuan chatbot', 'href' => route('admin.pengetahuan-chatbot.index'), 'ikon' => 'chat', 'aktif' => request()->routeIs('admin.pengetahuan-chatbot*'), 'grup' => 'Kelola'];
            }
            if (auth()->user()->punyaRole('pasien')) {
                $menu[] = ['label' => 'Profil', 'href' => route('pasien.profil'), 'ikon' => 'user', 'aktif' => request()->routeIs('pasien.profil*'), 'grup' => 'Layanan'];
                $menu[] = ['label' => 'Kartu QR', 'href' => route('pasien.qr'), 'ikon' => 'kartu', 'aktif' => request()->routeIs('pasien.qr'), 'grup' => 'Layanan'];
                $menu[] = ['label' => 'Tanya chatbot', 'href' => route('pasien.chat'), 'ikon' => 'chat', 'aktif' => request()->routeIs('pasien.chat*'), 'grup' => 'Layanan'];
            }
        @endphp

        <aside class="sidebar fixed inset-y-0 left-0 z-20 flex w-[264px] flex-col overflow-hidden border-r border-line bg-surface px-4 pb-5 pt-6 max-[820px]:hidden">
            <div class="brand flex items-center gap-2.5 px-2">
                <span class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-[10px] bg-primary text-white">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 3v18M3 12h18"/></svg>
                </span>
                <span class="hide-collapsed brand-text">
                    <span class="block text-[15px] font-bold tracking-tight">Klinik Sehat</span>
                    <span class="block text-xs text-faint">Sistem Informasi</span>
                </span>
                <button id="toggle-sidebar" type="button" title="Ciutkan / lebarkan sidebar"
                        class="ml-auto flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-muted hover:bg-slate-50 hover:text-ink">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg>
                </button>
            </div>

            <nav class="flex flex-col gap-0.5">
                @foreach ($menu as $item)
                    @php
                        $grupBaru = ($item['grup'] ?? '') !== ($menu[$loop->index - 1]['grup'] ?? null);
                    @endphp
                    @if ($grupBaru)
                        <p class="hide-collapsed nav-section px-2.5 {{ $loop->first ? 'pt-6' : 'pt-5' }} pb-1.5 text-[11px] font-semibold tracking-[.07em] text-faint uppercase">{{ $item['grup'] }}</p>
                    @endif
                    <a href="{{ $item['href'] }}" data-label="{{ $item['label'] }}"
                        @if ($item['aktif']) aria-current="page" @endif
                        class="nav-item flex h-10 items-center gap-2.5 rounded-[10px] px-2.5 text-sm no-underline {{ $item['aktif'] ? 'bg-primary-soft font-semibold text-primary' : 'text-muted hover:bg-slate-50 hover:text-ink' }}">
                        @switch($item['ikon'])
                            @case('grid')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                            @break
                            @case('user-plus')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                            @break
                            @case('scan')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M3 12h18"/></svg>
                            @break
                            @case('antrean')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            @break
                            @case('user')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            @break
                            @case('kartu')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                            @break
                            @case('kalender')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            @break
                            @case('chat')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-8 8H8l-5 3 1.4-4.2A8 8 0 1 1 21 12z"/></svg>
                            @break
                        @endswitch
                        <span class="hide-collapsed">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>
    @endauth

    <div class="main min-h-screen @auth ml-[264px] max-[820px]:ml-0 @endauth">
        @auth
        <header class="sticky top-0 z-10 flex h-16 items-center justify-between gap-4 border-b border-line bg-surface pl-4 pr-10 max-[820px]:pr-6">
            <div class="flex min-w-0 items-center gap-3.5">
                <p class="truncate text-[13px] text-faint">@yield('crumb', 'Sistem Klinik Kesehatan')</p>
            </div>

            <div class="flex items-center">
                @auth
                    <div class="relative">
                        <button id="tombol-profil" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="menu-profil" aria-label="Menu profil {{ auth()->user()->name }}"
                                class="flex items-center gap-2.5 rounded-xl p-1.5 hover:bg-slate-50">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary ring-1 ring-primary/15">{{ $inisial }}</span>
                            <span class="hidden text-left leading-tight sm:block">
                                <span class="block text-[13px] font-semibold text-ink">{{ auth()->user()->name }}</span>
                                <span class="block text-xs text-muted">{{ auth()->user()->role }}</span>
                            </span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="text-faint" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>

                        <div id="menu-profil" role="menu" data-open="false" aria-hidden="true" class="profil-menu absolute right-0 z-20 mt-2 w-56 rounded-xl border border-line bg-surface p-1.5 shadow-lg">
                            <div class="px-3 py-2.5">
                                <p class="truncate text-[13px] font-semibold text-ink">{{ auth()->user()->name }}</p>
                                <p class="mt-0.5 truncate text-[12px] text-muted">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="my-1 border-t border-line-soft"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" role="menuitem" class="flex h-10 w-full items-center gap-2.5 rounded-lg px-3 text-sm font-medium text-ink hover:bg-slate-50">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="text-muted" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </header>
        @endauth

        <main class="@yield('main-class', 'mx-auto max-w-[1180px] px-10 pt-9 pb-14 max-[1080px]:px-6 max-[1080px]:pt-7')">
            @unless (request()->routeIs('login'))
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
            @endunless

            @yield('isi')
        </main>
    </div>
    @stack('scripts')
</body>
</html>
