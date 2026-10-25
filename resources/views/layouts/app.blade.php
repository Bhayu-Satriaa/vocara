<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Sistem Klinik Kesehatan')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    @auth
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
                <div>
                    <p class="font-semibold">Sistem Klinik Kesehatan</p>
                    <p class="text-xs text-slate-500">
                        {{ auth()->user()->name }} &middot; {{ auth()->user()->role }}
                    </p>
                </div>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>
                    @if (auth()->user()->punyaRole('admin'))
                        <a href="{{ url('/admin') }}" class="hover:underline">Panel admin</a>
                    @endif
                    @if (auth()->user()->punyaRole('pasien'))
                        <a href="{{ route('pasien.profil') }}" class="hover:underline">Profil</a>
                        <a href="{{ route('pasien.qr') }}" class="hover:underline">Kartu QR</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-slate-300 px-3 py-1 hover:bg-slate-50">
                            Keluar
                        </button>
                    </form>
                </nav>
            </div>
        </header>
    @endauth

    <main class="mx-auto max-w-5xl px-4 py-8">
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
</body>
</html>
