# Design.md — Pedoman Tampilan Vocara

Konteks singkat untuk siapa pun (agent/manusia) yang menggarap tampilan halaman lain,
supaya hasilnya konsisten dengan `dashboard/petugas.blade.php` yang sudah jadi.
Aturan fitur/keamanan tetap di `AGENTS.md` — dokumen ini **hanya soal tampilan**.

## Di mana semuanya

| File | Isi |
|---|---|
| `resources/views/layouts/app.blade.php` | Kerangka semua halaman: sidebar, topbar, flash message, `@yield('isi')` |
| `resources/css/app.css` | Token warna (`@theme`) + aturan sidebar mengecil & tooltip |
| `resources/js/app.js` | Tombol toggle sidebar (5 baris) |
| `resources/views/dashboard/petugas.blade.php` | Contoh halaman yang sudah sesuai pedoman — tiru polanya |

Halaman baru cukup `@extends('layouts.app')` + `@section('judul')` + `@section('isi')`.
Tailwind 4 lewat Vite: `npm run dev` saat mengembangkan, `npm run build` sebelum demo
(tanpa `public/build/manifest.json` semua halaman `@vite` error).

## Warna

Pakai **token** (class Tailwind), jangan hex lepas. Token didefinisikan di
`resources/css/app.css` → `@theme`.

| Token | Hex | Pakai untuk |
|---|---|---|
| `bg-canvas` | `#f5f7fa` | Latar halaman |
| `bg-surface` | `#ffffff` | Kartu, sidebar, topbar |
| `border-line` | `#e6eaf1` | Garis tepi kartu, input, pemisah kuat |
| `border-line-soft` | `#eef1f6` | Pemisah antar baris daftar/tabel |
| `text-ink` | `#0f172a` | Judul & teks utama |
| `text-muted` | `#64748b` | Teks sekunder |
| `text-faint` | `#94a3b8` | Label kecil, keterangan, teks disabled |
| `bg-primary` / `hover:bg-primary-hover` | `#1d4ed8` / `#1e40af` | Tombol utama, link aktif |
| `bg-primary-soft` + `text-primary` | `#e8eefb` / `#1d4ed8` | Nav aktif, avatar, latar pill |
| `bg-accent-soft` + `text-accent` | `#e7eefb` / `#2563eb` | Tile ikon biru, pill "5 tugas" |
| `bg-amber-soft` + `text-amber-ink` | `#fcf3da` / `#d97706` | Tile ikon kuning |
| `bg-green-soft` + `text-green-ink` | `#e4f4ea` / `#16a34a` | Tile ikon hijau |
| `bg-indigo-soft` + `text-indigo-ink` | `#ececfb` / `#4f46e5` | Tile ikon ungu |

Badge status (dipakai saat tabel/riwayat sudah dibuat) — **tambahkan token-nya dulu di
`@theme`**, jangan hex lepas:
`diperiksa` `#dbeafe`/`#1d4ed8` · `menunggu` `#fef3c7`/`#b45309` ·
`selesai` `#dcfce7`/`#15803d` · `batal` `#fee2e2`/`#dc2626`.

## Tipografi

- Font: **Instrument Sans** (sudah dikonfigurasi di `vite.config.js`, jangan ganti).
- Judul halaman: `text-[30px] font-bold tracking-tight leading-tight` (di bawah 820px jadi `text-2xl`).
- Judul kartu: `text-[17px] font-semibold tracking-tight`.
- Isi: `text-sm` (14px), teks sekunder `text-[13px]`–`text-[13.5px] text-muted`.
- Label kolom/section: `text-[11px] uppercase tracking-[.07em] text-faint font-semibold`.
- Nomor RM, token, jam: monospace (`font-mono`) supaya mudah dipindai mata.

## Komponen (kelas siap salin)

**Kartu**
```
rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]
```

**Tombol utama** — `inline-flex h-[38px] items-center gap-2 rounded-[9px] bg-primary px-3.5
text-[13.5px] font-semibold text-white no-underline hover:bg-primary-hover`
(lebar penuh: ganti jadi `flex w-full justify-center`).

**Tombol sekunder** — `h-[38px] rounded-[9px] border border-line bg-surface px-3.5
text-[13.5px] font-semibold text-ink hover:bg-slate-50`.

**Pill** — `rounded-full bg-accent-soft px-2.5 py-1 text-[11.5px] font-semibold text-primary`.

**Tile ikon** — kotak `h-10 w-10 rounded-[10px]` berisi SVG stroke 1.8, warna
`bg-<warna>-soft text-<warna>-ink` (SVG: `viewBox="0 0 24 24"`, `aria-hidden="true"`).

**Daftar baris** — `<ul class="mt-5 divide-y divide-line-soft">`, tiap `<li class="flex gap-3.5 py-3.5 first:pt-0">`.

**Input** — `h-10 w-full rounded-[10px] border border-line bg-surface px-3 text-sm`;
pesan error `mt-1 text-sm text-red-600`.

**Keterangan/catatan** — `rounded-[10px] border border-dashed border-line bg-slate-50
px-3.5 py-3 text-[12.5px] text-faint`.

## Layout

- Sidebar tetap `264px` di kiri (`fixed`), konten `ml-[264px]`, isi `max-w-[1180px] px-10 pt-9 pb-14`.
- Topbar `sticky` tinggi `h-16`, latar **`bg-slate-50`** — sengaja dibuat "putih tapi tidak
  sama persis" dengan sidebar (sidebar `bg-surface` putih, topbar `bg-slate-50`, isi
  `bg-canvas`), supaya batasnya terlihat tanpa garis yang mencolok. Padding `pl-4 pr-10` —
  kiri tombol toggle, kanan identitas user + tombol Keluar. **Dua tombol di topbar wajib
  seragam**: `h-[38px] rounded-[9px] border border-line bg-slate-50 px-3.5 font-semibold
  text-ink hover:bg-slate-100` (toggle hanya berbeda `w-[38px]` tanpa teks). Latar tombol
  **sama persis dengan topbar** (`bg-slate-50`) supaya menyatu; yang membedakan hanya
  garis `border-line`, dan hover naik satu langkah ke `slate-100` biar tetap terasa
  sebagai tombol. Jangan pakai `bg-surface` — tombol akan terlihat putih menyolok.
- Gutter: `≤1080px` → padding `px-6` dan grid jadi 1 kolom; `≤820px` → sidebar sembunyi;
  `≤640px` → judul halaman menumpuk di atas tombol aksi.
- **Sidebar bisa mengecil (76px)**: state di `localStorage['vocara.sidebar']`, diterapkan
  sebagai atribut `<html data-collapsed>` oleh skrip inline di layout sebelum paint.
  Saat mengecil, elemen class `hide-collapsed` disembunyikan dan tiap `.nav-item`
  menampilkan tooltip dari atribut `data-label`. Aturannya di `resources/css/app.css`.

## Animasi

Prinsipnya: **jangan animasikan semuanya.** Hanya dua hal yang bergerak, sisanya diam
(satu tampilan paling banyak 1–2 elemen bergerak — tambah hanya kalau ada alasan UX).

| Yang bergerak | Durasi | Easing | Sumber |
|---|---|---|---|
| Hover/focus elemen interaktif (`a`, `button`, `input`) — warna latar, border, teks, ring | 150ms | ease | aturan global `app.css` |
| Sidebar mengecil/lebar (width, padding, margin konten) | 220ms | ease | blok `.sidebar` / `.main` |
| Label sidebar (`hide-collapsed`) saat **dibuka**: fade menyusul setelah jeda 80ms; saat ditutup fade 100ms tanpa jeda | 180ms / 100ms | ease | blok `.hide-collapsed` |
| Tooltip label sidebar (fade) | 150ms | ease | blok `.nav-item::after` |
| Isi halaman masuk: fade + naik 6px, **sekali** saat halaman dimuat | 250ms | ease-out | `@keyframes isi-masuk` pada `main` |

Yang **tidak** dianimasikan (jangan ditambahkan tanpa alasan kuat): kartu/tabel berurutan,
angka statistik, zoom/scale tombol, parallax, animasi berulang (loop), dan apa pun lebih
dari 300ms.

Aturan wajib:
- Semua gerak sudah otomatis **dimatikan** oleh `@media (prefers-reduced-motion: reduce)`
  di `app.css` — jangan menambah transisi/animasi di luar aturan global tanpa ikut
  menjaga blok ini (aksesibilitas, prioritas utama).
- Jangan mengandalkan `transitionend`/`animationend` untuk kebenaran data status.
- Posisi ikon/teks saat rail mengecil diatur lewat **`padding-inline`** (brand 7px,
  nav-item 17px), bukan `justify-content` — properti itu tidak bisa dianimasikan dan
  akan "loncat" saat rail membuka. Jangan dikembalikan ke `justify-content`.
- Jangan mengganti `visibility`+`opacity` pada `.hide-collapsed` dengan `display:none`:
  `display` tidak bisa ber-transisi sehingga teks muncul seketika saat rail masih sempit
  (terlihat patah) — itu bug yang pernah terjadi.
- Catatan: sidebar memakai transisi `width` (bukan transform) karena alasannya sederhana
  dan ukurannya tetap; kalau nanti terasa lambat di mesin klien, itu satu-satunya
  kandidat yang perlu diubah.

## Navigasi per role

Menu dibangun di `layouts/app.blade.php` (array `$menu`). **Tautkan hanya halaman yang
route-nya sudah ada** — jangan membuat menu untuk modul yang belum dibuat:

- semua role: `Dashboard`
- `petugas`, `admin`: `Scan QR pasien` → `route('petugas.scan')`
- `admin`: `Panel admin` → `url('/admin')`
- `pasien`: `Profil`, `Kartu QR` → `route('pasien.profil')`, `route('pasien.qr')`

Ikon baru ditambahkan di blok `@switch($item['ikon'])` (gaya: stroke line, bukan filled).

## Panduan per kelompok

Setiap kelompok mengerjakan file di wilayahnya sendiri: view di `resources/views/<wilayah>/`
dan route di `routes/<wilayah>.php` (jangan menambah route di `web.php`). Sebelum menulis
tampilan baru: baca bagian **Komponen** di atas, lalu buka `dashboard/petugas.blade.php`
sebagai acuan bentuk. Aturan fitur/keamanan tetap di `AGENTS.md`.

**Umum untuk semua kelompok**
- Semua halaman `@extends('layouts.app')` — jangan menulis `<html>`/header/sidebar sendiri.
- Tambahkan tautan ke menu sidebar hanya lewat array `$menu` di `layouts/app.blade.php`,
  dan **hanya untuk route yang sudah dibuat**. Route baru = tugas kelompok wilayah itu.
- Tata letak responsif: kerangka sudah menangani ≤1080px, ≤820px, ≤640px; halaman baru
  cukup memakai `grid ... max-[1080px]:grid-cols-1` untuk dua kolom.
- Satu tombol utama (`bg-primary`) per halaman; aksi sekunder pakai tombol outline.
- Ikon baru: SVG stroke line `viewBox="0 0 24 24"` + `aria-hidden="true"`, jangan emoji.

**Kelompok pasien** — `routes/pasien.php`. Halaman Profil, Lengkapi Profil, dan Kartu QR
sudah ada: poles memakai token, jangan ubah urutan isinya. Untuk booking/riwayat baru:
kartu ringkasan + daftar baris (`divide-line-soft`); nomor RM selalu `font-mono`.
Status kartu QR: *belum terverifikasi* → pill `bg-amber-soft text-amber-ink`,
*terverifikasi* → `bg-green-soft text-green-ink`. Jangan tambahkan field NIK.

**Kelompok petugas** — `routes/petugas.php`. `/petugas/scan` sudah jadi, jadikan acuan form (label `text-[12.5px] font-semibold`, input `h-10`, tombol utama
full-width). Modul antrean / konfirmasi booking menyusul: memakai pola **tabel**
(lihat Kelompok dokter) plus badge status. Pembayaran dan kwitansi **bukan** wilayah
petugas lagi — lihat Kelompok admin.

**Kelompok dokter** — `routes/dokter.php`. Pola umum: baris kartu ringkasan angka
(`text-2xl font-bold` + label `text-xs text-faint`) di atas, lalu **tabel daftar pasien**:
header `bg-slate-50 text-[11px] uppercase tracking-[.07em] text-faint`, baris
`divide-line-soft`, kolom RM/jam `font-mono`, kolom aksi `text-primary`. Form SOAP memakai
komponen input + label di atas; riwayat/kunjungan memakai badge status (4 warna di bawah).
Data medis sensitif — jangan ditampilkan tanpa lewat kontrol role (AGENTS.md).

**Kelompok apoteker** — `routes/apoteker.php`. Daftar resep: tabel + badge status resep
(`menunggu` amber, `disiapkan` biru, `selesai` hijau). Isi resep tampil sebagai teks
dari dokter (nama obat, dosis, aturan pakai). **Tidak ada tampilan stok** — jangan
menambah kolom stok, penanda "stok menipis", atau tombol stok masuk/keluar. Etiket obat
tetap dicetak.

**Kelompok admin** — data master & laporan di `routes/admin-klinik.php`, back office
Filament v5 di `/admin` (`AdminPanelProvider`, di luar `resources/` tapi termasuk tampilan,
jadi boleh disentuh). Dashboard admin memakai kartu angka pola di atas; ekspor PDF/Excel
cukup tombol sekunder di kanan judul. Panel Filament masih tema bawaan (Amber) — bila di-
redesign, ambil aksen `#1d4ed8` + latar `#f5f7fa` supaya menyatu.

**Kelompok AI (chatbot & speech)** — gelembung percakapan: jawaban bot
`bg-surface border border-line rounded-2xl`, pesan user `bg-primary text-white rounded-2xl`;
input teks + tombol mikrofon (tile ikon `bg-accent-soft text-accent`). **Disclaimer
"bukan pengganti dokter" wajib tampil** pada triase dan saran (AGENTS.md). Badge status
untuk hasil triase memakai empat warna berikut.

Badge status kunjungan (`booking`, `menunggu`, `diperiksa`, `farmasi`, `pembayaran`,
`selesai`, `batal`) — pola `rounded-full px-2.5 py-1 text-[11.5px] font-semibold` + warna:
`diperiksa` `#dbeafe`/`#1d4ed8` · `menunggu` `#fef3c7`/`#b45309` · `selesai` `#dcfce7`/`#15803d`
· `batal` `#fee2e2`/`#dc2626`. **Tambahkan token-nya dulu di `@theme`** (lihat tabel warna),
jangan memakai hex lepas di view.

Badge status pembayaran (halaman admin dan riwayat pasien) memakai token yang sudah ada —
tanpa token baru: `lunas` = `bg-green-soft text-green-ink`, `belum lunas` =
`bg-amber-soft text-amber-ink`. Toggle status + metode (`transfer` / `cash`) hanya muncul
untuk role admin. Kwitansi cukup kartu + `font-mono` untuk nominal.

## Larangan singkat (detail di AGENTS.md)

- Jangan ada field/kolom **NIK**, jangan ada menu **surat sakit**.
- Triase/saran chatbot wajib menampilkan disclaimer "bukan pengganti dokter".
- Tampilkan teks sesuai yang ada di aplikasi; jangan mengarang data statistik di kartu.
- Navigasi & tombol jangan menunjuk halaman yang belum ada route-nya.

## Checklist halaman baru

1. `@extends('layouts.app')`, isi di `@section('isi')`.
2. Pakai token & kelas komponen di atas; tambah token baru di `@theme` bila perlu.
3. Label sedang: tambahkan `@section('crumb', 'Nama Halaman')`.
4. `npm run build`, lalu cek lebar 1440px dan 768px.
