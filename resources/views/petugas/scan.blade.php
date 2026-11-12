@extends('layouts.app')

@section('judul', 'Scan QR Pasien')
@section('crumb', 'Scan QR pasien')

@section('isi')
    <div class="mx-auto max-w-xl">
        <div class="mb-6">
            <h1 class="mb-1.5 text-[28px] leading-tight font-bold tracking-tight text-ink max-[820px]:text-2xl">
                Scan QR pasien
            </h1>
            <p class="text-sm text-muted">
                Pindai kode QR pada kartu pasien langsung menggunakan kamera atau masukkan token secara manual.
            </p>
        </div>

        {{-- Tab Navigasi Mode --}}
        <div class="mb-5 flex rounded-xl border border-line bg-surface p-1 shadow-xs">
            <button type="button" id="tab-kamera-btn"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg py-2 text-[13.5px] font-semibold transition-all bg-primary text-white shadow-xs">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                Kamera Langsung
            </button>
            <button type="button" id="tab-manual-btn"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg py-2 text-[13.5px] font-medium text-muted transition-all hover:text-ink">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h.01M10 8h.01M14 8h.01M18 8h.01M8 12h.01M12 12h.01M16 12h.01M7 16h10"/>
                </svg>
                Input Manual
            </button>
        </div>

        {{-- Form Pencarian Terpadu --}}
        <form id="form-scan" method="POST" action="{{ route('petugas.scan.cari') }}"
              class="rounded-[16px] border border-line bg-surface p-6 shadow-[0_1px_3px_rgba(16,24,40,.06)]">
            @csrf

            {{-- 1. Panel Kamera Pemindai --}}
            <div id="panel-kamera">
                {{-- Selector Kamera --}}
                <div class="mb-3 flex items-center justify-between gap-3">
                    <label for="camera-select" class="text-xs font-semibold text-muted">Pilih Perangkat Kamera:</label>
                    <select id="camera-select" class="h-8 max-w-[220px] rounded-lg border border-line bg-canvas px-2.5 text-xs text-ink focus:border-primary focus:outline-none">
                        <option value="">Memuat daftar kamera...</option>
                    </select>
                </div>

                {{-- Viewport Kamera --}}
                <div class="relative overflow-hidden rounded-xl border border-line bg-slate-950">
                    <div id="reader" class="w-full aspect-[4/3] sm:aspect-[16/11]"></div>

                    {{-- Overlay Placeholder / Belum Aktif --}}
                    <div id="scanner-idle" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/90 p-6 text-center text-white">
                        <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-white backdrop-blur-xs">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/>
                                <path d="M12 8v8M8 12h8"/>
                            </svg>
                        </div>
                        <p class="font-semibold text-[15px]">Kamera Belum Aktif</p>
                        <p class="mt-1 text-xs text-slate-300 max-w-xs">
                            Klik tombol di bawah untuk menyalakan kamera dan mengarahkan kartu QR pasien.
                        </p>
                    </div>

                    {{-- Overlay Sukses / Memproses --}}
                    <div id="scanner-processing" class="hidden absolute inset-0 flex flex-col items-center justify-center bg-emerald-950/90 p-6 text-center text-white backdrop-blur-xs">
                        <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500/20 text-emerald-400">
                            <svg class="h-8 w-8 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                        </div>
                        <p class="font-bold text-base text-emerald-300">QR Terdeteksi!</p>
                        <p class="mt-1 text-xs text-emerald-100 font-mono" id="detected-token-text">Mencari data pasien...</p>
                    </div>
                </div>

                {{-- Status & Kontrol --}}
                <div class="mt-4 flex flex-col sm:flex-row items-center gap-2.5">
                    <button type="button" id="btn-toggle-camera"
                            class="flex h-10 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-primary text-[13.5px] font-semibold text-white transition-colors hover:bg-primary-hover shadow-xs">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                        <span>Mulai Kamera</span>
                    </button>
                </div>

                {{-- Pesan Error / Izin --}}
                <div id="camera-error" class="hidden mt-3 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-700"></div>

                <div class="mt-4 rounded-xl border border-line-soft bg-slate-50/70 p-3.5 text-[12.5px] text-muted">
                    <p class="font-medium text-ink flex items-center gap-1.5 mb-1">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        Petunjuk Pemindaian:
                    </p>
                    <ul class="list-disc pl-5 space-y-0.5 text-faint">
                        <li>Pastikan browser telah diizinkan untuk mengakses kamera.</li>
                        <li>Posisikan kartu QR atau layar ponsel pasien stabil di depan kamera hingga terbaca otomatis.</li>
                    </ul>
                </div>
            </div>

            {{-- 2. Panel Input Manual --}}
            <div id="panel-manual" class="hidden">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-[11px] bg-accent-soft text-accent">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/>
                        <path d="M3 12h18"/>
                    </svg>
                </div>

                <label for="token" class="mb-1.5 block text-[12.5px] font-semibold text-ink">Token QR Pasien</label>
                <input id="token" name="token" value="{{ old('token') }}" autocomplete="off"
                       placeholder="cth. 40b17330-1234-4abc-8def-0123456789ab"
                       class="h-10 w-full rounded-[10px] border border-line bg-surface px-3 font-mono text-sm text-ink placeholder:text-faint focus:border-primary focus:ring-2 focus:ring-primary-soft focus:outline-none">

                @error('token')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <button type="submit" id="btn-submit-manual"
                        class="mt-4 flex h-[38px] w-full cursor-pointer items-center justify-center rounded-[9px] bg-primary text-[13.5px] font-semibold text-white hover:bg-primary-hover shadow-xs">
                    Cari Pasien
                </button>

                <p class="mt-4 rounded-[10px] border border-dashed border-line bg-slate-50 px-3.5 py-3 text-[12.5px] text-faint">
                    Bisa digunakan juga jika Anda menggunakan scanner barcode/QR USB fisik yang terhubung ke komputer.
                </p>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tab switching
    const tabKameraBtn = document.getElementById('tab-kamera-btn');
    const tabManualBtn = document.getElementById('tab-manual-btn');
    const panelKamera = document.getElementById('panel-kamera');
    const panelManual = document.getElementById('panel-manual');
    const tokenInput = document.getElementById('token');
    const formScan = document.getElementById('form-scan');

    // Camera scanner elements
    const cameraSelect = document.getElementById('camera-select');
    const btnToggleCamera = document.getElementById('btn-toggle-camera');
    const scannerIdle = document.getElementById('scanner-idle');
    const scannerProcessing = document.getElementById('scanner-processing');
    const detectedTokenText = document.getElementById('detected-token-text');
    const cameraError = document.getElementById('camera-error');

    let html5QrCode = null;
    let isScanning = false;
    let cameraList = [];

    // Audio feedback (beep)
    function playBeep() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime); // 880 Hz
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.18);
            osc.start();
            osc.stop(ctx.currentTime + 0.18);
        } catch (e) {
            console.warn('Audio feedback tidak didukung/dibatasi browser:', e);
        }
    }

    // Switch tab functions
    function switchTab(mode) {
        if (mode === 'kamera') {
            tabKameraBtn.className = 'flex flex-1 items-center justify-center gap-2 rounded-lg py-2 text-[13.5px] font-semibold transition-all bg-primary text-white shadow-xs';
            tabManualBtn.className = 'flex flex-1 items-center justify-center gap-2 rounded-lg py-2 text-[13.5px] font-medium text-muted transition-all hover:text-ink';
            panelKamera.classList.remove('hidden');
            panelManual.classList.add('hidden');
        } else {
            tabManualBtn.className = 'flex flex-1 items-center justify-center gap-2 rounded-lg py-2 text-[13.5px] font-semibold transition-all bg-primary text-white shadow-xs';
            tabKameraBtn.className = 'flex flex-1 items-center justify-center gap-2 rounded-lg py-2 text-[13.5px] font-medium text-muted transition-all hover:text-ink';
            panelManual.classList.remove('hidden');
            panelKamera.classList.add('hidden');
            stopCamera();
            tokenInput.focus();
        }
    }

    tabKameraBtn.addEventListener('click', () => switchTab('kamera'));
    tabManualBtn.addEventListener('click', () => switchTab('manual'));

    // Inisialisasi daftar kamera
    function initCameraList() {
        if (typeof Html5Qrcode === 'undefined') {
            showError('Pustaka pemindai QR belum termuat. Periksa koneksi internet Anda atau gunakan input manual.');
            return;
        }

        Html5Qrcode.getCameras().then(devices => {
            cameraList = devices;
            cameraSelect.innerHTML = '';
            if (devices && devices.length) {
                devices.forEach((device, index) => {
                    const opt = document.createElement('option');
                    opt.value = device.id;
                    opt.text = device.label || `Kamera ${index + 1}`;
                    cameraSelect.appendChild(opt);
                });
                // Prefer kamera belakang jika di smartphone
                const backCam = devices.find(d => /back|rear|environment/i.test(d.label));
                if (backCam) {
                    cameraSelect.value = backCam.id;
                }
            } else {
                cameraSelect.innerHTML = '<option value="">Tidak ada kamera terdeteksi</option>';
            }
        }).catch(err => {
            console.warn('Gagal memuat kamera:', err);
            cameraSelect.innerHTML = '<option value="">Kamera bawaan / Default</option>';
        });
    }

    function showError(msg) {
        cameraError.textContent = msg;
        cameraError.classList.remove('hidden');
    }

    function hideError() {
        cameraError.textContent = '';
        cameraError.classList.add('hidden');
    }

    // Mulai kamera
    async function startCamera() {
        hideError();
        if (typeof Html5Qrcode === 'undefined') {
            showError('Library pemindai belum siap. Silakan refresh halaman atau gunakan input manual.');
            return;
        }

        try {
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode('reader');
            }

            const cameraId = cameraSelect.value || { facingMode: "environment" };

            const config = {
                fps: 10,
                qrbox: (viewfinderWidth, viewfinderHeight) => {
                    const minDim = Math.min(viewfinderWidth, viewfinderHeight);
                    const size = Math.floor(minDim * 0.72);
                    return { width: size, height: size };
                },
                aspectRatio: 1.333
            };

            await html5QrCode.start(
                cameraId,
                config,
                onScanSuccess,
                () => {} // abaikan frame tanpa QR
            );

            isScanning = true;
            scannerIdle.classList.add('hidden');
            btnToggleCamera.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>
                </svg>
                <span>Hentikan Kamera</span>
            `;
            btnToggleCamera.className = 'flex h-10 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-slate-800 text-[13.5px] font-semibold text-white transition-colors hover:bg-slate-900 shadow-xs';
        } catch (err) {
            console.error('Error menyalakan kamera:', err);
            isScanning = false;
            let msg = 'Gagal mengakses kamera. ';
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                msg += 'Izin akses kamera ditolak di browser Anda. Harap berikan izin akses kamera pada bilah alamat browser.';
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                msg += 'Perangkat kamera tidak ditemukan di komputer/perangkat ini.';
            } else {
                msg += (err.message || 'Pastikan browser Anda berjalan pada HTTPS atau localhost.');
            }
            showError(msg);
        }
    }

    // Hentikan kamera
    async function stopCamera() {
        if (html5QrCode && isScanning) {
            try {
                await html5QrCode.stop();
            } catch (e) {
                console.warn('Gagal menghentikan kamera:', e);
            }
        }
        isScanning = false;
        scannerIdle.classList.remove('hidden');
        btnToggleCamera.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="5 3 19 12 5 21 5 3"/>
            </svg>
            <span>Mulai Kamera</span>
        `;
        btnToggleCamera.className = 'flex h-10 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-primary text-[13.5px] font-semibold text-white transition-colors hover:bg-primary-hover shadow-xs';
    }

    // Handler saat scan berhasil
    function onScanSuccess(decodedText) {
        if (!decodedText) return;

        playBeep();

        // Ambil token UUID bersih jika ada format URL atau spasi
        let cleanToken = decodedText.trim();
        const uuidMatch = cleanToken.match(/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i);
        if (uuidMatch) {
            cleanToken = uuidMatch[0];
        }

        // Tampilkan indikator memproses
        scannerProcessing.classList.remove('hidden');
        detectedTokenText.textContent = cleanToken;

        // Isi form dan kirim
        tokenInput.value = cleanToken;

        stopCamera().finally(() => {
            formScan.submit();
        });
    }

    // Toggle button listener
    btnToggleCamera.addEventListener('click', function () {
        if (isScanning) {
            stopCamera();
        } else {
            startCamera();
        }
    });

    // Ganti kamera saat select berubah
    cameraSelect.addEventListener('change', function () {
        if (isScanning) {
            stopCamera().then(() => startCamera());
        }
    });

    // Otomatis buka tab manual jika ada error validasi backend lama
    @if ($errors->has('token') || old('token'))
        switchTab('manual');
    @else
        initCameraList();
    @endif
});
</script>
@endpush
