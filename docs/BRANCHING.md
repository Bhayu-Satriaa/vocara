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

1. `php artisan test` lulus (baseline repo: **58 test** — PR tidak boleh
   menurunkannya).
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
- Setelah clone baru: `npm install && npm run build` dan
  `php artisan filament:assets` (aset panel).
- Login Google pasien butuh kredensial di `.env` sendiri; `.env` tidak pernah
  di-commit dan tidak pernah dibagikan.

## 7. Urutan modul dan pemiliknya

Sudah jadi: skema + migrasi, model, seeder, login staf multi-role, panel admin
Filament, login Google pasien + kartu QR, dan **scan QR + verifikasi KTP petugas**.

Sedang / akan dikerjakan:

- **Multi profil (Ridwan)** — dikerjakan **lebih dulu** karena mengubah asumsi
  "1 akun = 1 pasien" yang saat ini dijaga database (`patients.user_id` UNIQUE).
  Selama belum selesai, anggota lain **jangan** membangun fitur di atas asumsi itu.
- **Chatbot + AI speech (Jian)** — fitur wajib, dikerjakan setelah modul inti.
- **Design (Juli, dibantu Nabil)** — token design Hangat ditetapkan dulu di
  `resources/css/app.css`, lalu halaman per halaman.
- **Janji temu / booking (Nabil)**.

Belum ada pemiliknya — perlu keputusan di pertemuan:

- Antrean & check-in petugas (nomor antrean, kunjungan masuk antrean)
- Pemeriksaan dokter (SOAP, tanda vital, ICD-10, resep)
- Farmasi / serah obat lewat QR (tanpa manajemen stok untuk sementara)
- Pembayaran & kwitansi (admin; status lunas/belum lunas, metode transfer/cash)
- Data master + statistik admin di panel Filament

## 8. Penanda demo

Untuk presentasi, tandai commit dengan tag sederhana (bukan semver serius):

```bash
git tag -a v0.1-scan-qr -m "Scan QR + verifikasi KTP"
git push origin v0.1-scan-qr
```

Usulan urutan tag: `v0.1-scan-qr` (sudah bisa) → `v0.2-multiprofil` →
`v0.3-janji-temu` → `v0.4-design-hangat` → `v1.0-chatbot-speech` (demo akhir).
