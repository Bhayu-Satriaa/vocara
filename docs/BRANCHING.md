# Panduan Branch dan Pull Request

Aturan kerja kelompok untuk repo ini. Dibuat supaya lima orang bisa mengerjakan
wilayahnya masing-masing tanpa saling menimpa. **Baca ini sebelum membuat branch
pertamamu.**

Dasar yang dipakai: **trunk-based ringan** — `main` selalu bisa di-demo, kerja di
branch pendek, masuk lewat Pull Request (PR). **Tidak ada branch `develop`**;
branch panjang membuat konflik menumpuk.

## 1. Nama branch: `<nama>/<bagian-pendek>`

Nama anggota dipakai sebagai penanda wilayah, dan tetap dipotong kecil per bagian
yang bisa dites sendiri.

Contoh:

| Anggota | Wilayah | Contoh branch |
|---|---|---|
| Jian | chatbot + AI speech | `jian/chatbot-faq`, `jian/speech-dikte-soap` |
| Juli | design web keseluruhan | `juli/design-token-hangat`, `juli/design-login` |
| Ridwan | multi profil (1 akun, banyak biodata) | `ridwan/multi-profil-skema`, `ridwan/multi-profil-crud` |
| Nabil | bantu design + janji temu booking | `nabil/design-komponen`, `nabil/janji-temu-booking` |
| Royan | belum dikonfirmasi | — |

**Satu branch = satu bagian yang bisa dites sendiri**, bukan seluruh wilayah.
Patokan: **merge dalam ≤ 3 hari kerja**. Kalau lebih lama, pecah jadi beberapa
bagian.

## 2. Alur kerja harian

```bash
git switch main && git pull                 # mulai dari main terbaru
git switch -c juli/design-login              # branch baru
# koding: satu fitur tuntas (migrasi → model → controller → route → view)
php artisan test                             # wajib lulus sebelum commit
git add -A
git commit -m "Rapikan halaman login pasien dengan tema hangat"
git fetch origin && git merge origin/main    # tarik main, selesaikan konflik DI BRANCH SENDIRI
git push -u origin juli/design-login         # lihat bagian 6 soal cara push
# buka Pull Request di GitHub, minta 1 orang review
# setelah di-approve: Merge di GitHub, lalu hapus branchnya
```

## 3. Aturan `main`

- **Dilarang push langsung ke `main`.** Semua perubahan lewat PR.
- Setiap PR butuh **1 approval**. PR yang menyentuh file bersama (`routes/`,
  `resources/views/layouts/app.blade.php`, `resources/css/app.css`, `docs/`,
  migrasi milik orang lain) **wajib di-approve Jian**.
- PR harus **up-to-date dengan `main`** sebelum merge (di GitHub:
  *Settings → Branches → Require branches to be up to date before merging*).
- **Jangan squash**, biarkan commit kecil tetap ada — itu cara anggota lain
  mengejar ketinggalan.
- Satu PR ≤ ~300 baris. Lebih dari itu, pecah.
- Branch yang tidak ada aktivitas > 5 hari: dibahas di Discord, lalu dihapus.

## 4. Zona rawan konflik (paling penting)

- **`routes/web.php`** — semua modul menambah route di file yang sama. Sedang
  dipecah per modul (`routes/pasien.php`, `routes/petugas.php`, dst.). Setelah
  pemecahan itu, konflik route jadi nol.
- **`resources/views/layouts/app.blade.php`** (menu atas) — tambahkan menu baru
  **satu baris di blok role masing-masing**, jangan merapikan bagian lain.
- **`resources/css/app.css` dan design global** — **hanya Juli** yang menyentuh.
  Anggota lain memakai kelas yang sudah ada; butuh kelas baru, minta ke Juli.
- **`database/seeders/`** — jangan edit seeder orang lain. Buat seeder sendiri
  (`XxxSeeder`) dengan `updateOrCreate` supaya idempoten, lalu panggil di
  `DatabaseSeeder`.
- **`docs/*.md`** — tambahkan bagian sendiri di bawah; keputusan baru ditambah
  sebagai baris baru di `docs/DECISIONS.md`, jangan menghapus baris lama.
- **`composer.json` / `composer.lock`** — paket baru **wajib disetujui kelompok
  dulu**, dan di-commit terpisah dari kodenya.
- **Database** — setiap orang memakai database lokal sendiri (`klinik_db`), reset
  dengan `php artisan migrate:fresh --seed`. **Jangan** menyambung ke DB orang
  lain; kalau tidak, pekerjaan orang lain bisa terhapus.

## 5. Yang dicek reviewer

1. `php artisan test` lulus (baseline repo: **72 test** — turun dari 85 setelah
   `PanelAdminTest.php` (13 test Filament) dihapus bersama pencabutan panel Filament di
   PR #8; test Blade pengganti untuk `/dashboard/akun-staf` belum ditulis). PR tidak boleh
   menurunkannya lebih jauh.
2. Tidak ada `.env`, `vendor/`, `public/build`, atau data pribadi ikut ter-commit.
3. Route baru dibatasi role yang benar (`middleware('role:...')`), dan halaman
   pasien tetap di balik `middleware('profil')`.
4. Menyentuh data medis atau QR → wajib menulis jejak di `activity_logs`
   (`qr_scan_logs` untuk pemindaian QR).
5. Satu fitur tuntas dan bisa dijalankan, bukan setengah jalan di banyak tempat.

## 6. Catatan teknis repo ini

- **Push dari WSL selalu lewat git Windows**, karena git WSL tidak punya
  credential helper:
  `/mnt/d/laragon/bin/git/bin/git.exe -c safe.directory='*' push -u origin <branch>`
  (dari Windows biasa cukup pakai tombol push di VS Code / GitHub Desktop).
- **Laragon Start All dulu** sebelum apa pun yang menyentuh MySQL. Error
  `2003 ... (10061)` berarti MySQL belum jalan, bukan password salah.
- PHP proyek ini **8.5.12 Laragon**
  (`D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64\php.exe`); 8.3.12 gagal
  `platform_check`.
- Setelah mengubah Blade: `php artisan view:clear`.
- Setelah clone baru: `composer install` (Filament sudah tidak dipakai, jadi tidak ada
  lagi langkah `filament:assets`), lalu `npm install && npm run build`.
- **`public/build` diabaikan git**, jadi tiap mesin punya hasil build sendiri. Kalau tampilan
  terlihat aneh padahal kode view/CSS sudah benar (mis. **menu profil di pojok kanan atas
  selalu terbuka dan tidak bisa dibuka-tutup** — pernah terjadi 5 Nov 2026: build dari 3 Nov
  14:44 sementara `app.css`/`app.js` sudah diubah 19:18, sehingga CSS `.profil-menu` dan JS
  dropdown-nya tidak ada di bundel), jalankan **`npm run build`** dulu — bukan menambal Blade.
- **Setelah menarik perubahan yang mencabut paket (mis. Filament/Livewire), wajib:**
  `composer install` **lalu** `php artisan view:clear`. View Blade yang sudah ter-compile
  masih memanggil kelas Livewire, dan tanpa `view:clear` semua halaman jadi 500 dengan
  `Class "Livewire\Mechanisms\ExtendBlade\ExtendBlade" not found` (pernah terjadi 3 Nov 2026:
  20 test gagal, 50 berkas di `storage/framework/views` masih menyebut Livewire).
- Login Google pasien butuh kredensial di `.env` sendiri; `.env` tidak pernah
  di-commit dan tidak pernah dibagikan.

## 7. Urutan modul dan pemiliknya

Sudah jadi: skema + migrasi, model, seeder, login staf multi-role, dashboard admin
Blade (`/dashboard`), login Google pasien + kartu QR, **scan QR + verifikasi KTP petugas**,
**pendaftaran pasien walk-in oleh petugas**, dan **chatbot janji temu (model 9Router)**.

Menunggu merge (PR #9–#12, Ridwan, sudah lolos uji 81/84/88/88 test — **merge berurutan
9 → 10 → 11 → 12** karena bertumpuk): data master pasien + filter akun staf, analitik
dashboard per bulan, CRUD jadwal dokter, dan zona waktu `Asia/Makassar`. Dua di antaranya
mengubah hal yang disebut dokumen: **URL petugas pindah ke `/dashboard/scan`** (dari
`/petugas/scan`) dan `APP_TIMEZONE` masuk konfigurasi.

Sedang / akan dikerjakan:

- **AI speech / suara (Jian)** — ditahan atas keputusan Jian 31 Okt 2026. Rancangan
  (Gemini Live `gemini-2.5-flash-native-audio-preview-12-2025`, suara `Kore`, cadangan
  TTS/STT bawaan browser) ada di `docs/DECISIONS.md` dan `docs/ROADMAP.md`.
- **Antrean petugas** — tombol check-in sudah ada di `/dashboard/scan`
  (`petugas.scan.checkin`, dari pekerjaan Nabil); nomor antrean belum dibuat.
- **Pemeriksaan dokter** (SOAP, tanda vital, ICD-10, resep) — belum ada pemilik.
- **Farmasi / serah obat lewat QR** (tanpa manajemen stok untuk sementara) — belum ada pemilik.
- **Pembayaran & kwitansi** (admin; toggle lunas/belum lunas + metode transfer/cash) —
  belum ada pemilik.

Sudah selesai dan ada pemiliknya: **multi profil pasien** (Ridwan, PR #4),
**design + dashboard admin Blade** (Juli, PR #8), **pendaftaran walk-in & verifikasi KTP
petugas** (Nabil), **chatbot janji temu + BookingService** (Jian, PR #7).
**Data master & statistik admin** sedang menunggu merge (PR #9–#12, lihat catatan di atas).

## 8. Penanda demo

Untuk presentasi, tandai commit dengan tag sederhana (bukan semver serius):

```bash
git tag -a v0.1-scan-qr -m "Scan QR + verifikasi KTP"
git push origin v0.1-scan-qr
```

Usulan urutan tag: `v0.1-scan-qr` (sudah bisa) → `v0.2-multiprofil` →
`v0.3-janji-temu` → `v0.4-design-hangat` → `v1.0-chatbot-speech` (demo akhir).
