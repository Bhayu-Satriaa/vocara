<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Pasien - {{ $pasien->nama_lengkap }} ({{ $pasien->no_rm }})</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .card-container {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 flex flex-col items-center justify-center min-h-screen p-4 text-slate-800">

    <div class="no-print mb-6 flex gap-3">
        <button onclick="window.print()" class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 shadow-sm">
            🖨️ Cetak Kartu Sekarang
        </button>
        <button onclick="window.close()" class="cursor-pointer rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Tutup
        </button>
    </div>

    {{-- Kartu Pasien Standar Ukuran ID Card --}}
    <div class="card-container w-[400px] rounded-2xl bg-white p-5 border border-slate-200 shadow-lg text-slate-900 relative overflow-hidden">
        {{-- Header Kartu --}}
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <div class="flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-white font-bold text-lg">
                    +
                </div>
                <div>
                    <h1 class="text-sm font-bold tracking-tight text-slate-900 leading-tight">KLINIK SEHAT</h1>
                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Kartu Identitas Pasien</p>
                </div>
            </div>
            <span class="rounded bg-blue-50 px-2 py-0.5 font-mono text-[11px] font-bold text-blue-700 border border-blue-200">
                {{ $pasien->no_rm }}
            </span>
        </div>

        {{-- Badan Kartu --}}
        <div class="mt-4 flex items-center justify-between gap-4">
            <div class="flex-1 space-y-1.5 text-xs">
                <div>
                    <span class="text-[10px] uppercase font-semibold text-slate-400">Nama Lengkap</span>
                    <p class="font-bold text-sm text-slate-900 leading-tight">{{ $pasien->nama_lengkap }}</p>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-1">
                    <div>
                        <span class="text-[10px] uppercase font-semibold text-slate-400">Tgl Lahir</span>
                        <p class="font-medium text-slate-800">{{ $pasien->tgl_lahir?->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-semibold text-slate-400">Jns Kelamin</span>
                        <p class="font-medium text-slate-800">{{ $pasien->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</p>
                    </div>
                </div>
                <div class="pt-1">
                    <span class="text-[10px] uppercase font-semibold text-slate-400">No. Kontak / WA</span>
                    <p class="font-medium text-slate-800">{{ $pasien->no_wa }}</p>
                </div>
            </div>

            {{-- QR Code --}}
            <div class="flex flex-col items-center justify-center rounded-xl bg-slate-50 p-2 border border-slate-200">
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(115)
                    ->margin(0)
                    ->generate($pasien->qr_token) !!}
                <span class="mt-1 text-[8px] font-mono text-slate-400">SCAN UNTUK CHECK-IN</span>
            </div>
        </div>

        {{-- Footer Kartu --}}
        <div class="mt-4 border-t border-slate-100 pt-2 flex items-center justify-between text-[9px] text-slate-400">
            <span>Tunjukkan kartu ini setiap berobat</span>
            <span>Berlaku di Semua Poli</span>
        </div>
    </div>

</body>
</html>

