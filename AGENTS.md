# AGENTS.md — Sistem Klinik Kesehatan

Konteks utama repo ini. **Agent/AI apa pun yang bekerja di sini wajib membaca dokumen
ini lebih dulu.** Bahasa komunikasi dengan pengguna dan pesan commit: **Bahasa
Indonesia**.

Dokumen ini sengaja dijaga singkat (dibaca ulang setiap sesi). Detail panjangnya ada di
`docs/` — jangan menyalin isi `docs/` ke sini.

## Project

Sistem informasi **klinik kesehatan berbasis web** untuk tugas Project Based Learning
(PBL) perkuliahan. Cakupannya alur pasien dari registrasi sampai pembayaran, ditambah
**chatbot AI** dan **AI Speech** sebagai fitur wajib, serta **QR code pasien** untuk
mempercepat registrasi dan check-in.

**Stack:** Laravel 13 (PHP 8.3+) + Blade + **Tailwind CSS 4** (Vite 8) · **MySQL**
(InnoDB, utf8mb4, database `klinik_db`) · **Filament 5** untuk panel admin · Laravel
Socialite (login Google) · simple-qrcode.

## Status saat ini (per 28 Okt 2026)

**Sudah jalan** (ada kode, route, dan feature test):

- Login **staf** multi-role (email + password) dan login **pasien** lewat **Google OAuth** (stateless).
- Halaman **Lengkapi Profil** (nomor WA wajib) + **multi-profile data diri** (diri sendiri/keluarga, ada penanda data diri yang aktif dipakai).
- **Kartu QR pasien** (gambar QR, token UUID).
- **Panel admin Filament** di `/admin` — baru resource akun staf.
- **Halaman scan QR petugas** di `/petugas/scan` (cari pasien dari token QR + verifikasi KTP).

**Belum dibuat** (jangan diklaim sudah ada): janji temu/booking, antrean, pemeriksaan dokter
(rekam medis SOAP), farmasi/resep, kasir/pembayaran, chatbot AI, AI Speech, laporan.

Total saat ini: 23 model, 28 migrasi, 6 feature test (`tests/Feature/`).

## Peta dokumen pendamping

| Dokumen | Isi |
|---|---|
| `README.md` | pengantar proyek + ringkasan fitur per role |
| `docs/FEATURES.md` | fitur per role dan alurnya |
| `docs/DATABASE.md` | struktur data/tabel |
| `docs/DECISIONS.md` | keputusan + hal yang masih terbuka (baca sebelum mengusulkan fitur) |
| `docs/BRANCHING.md` | aturan branch dan alur git |
| `docs/SPEC-PENJAGA-DUPLIKAT.md` | spec penjaga duplikat pasien (tanpa NIK) |
| `klinik_schema.sql` | skema database lengkap |
| `docs/boost-guidelines.md` | panduan tooling Laravel Boost bawaan skeleton — **bukan** aturan proyek |

## Keputusan final (jangan diusulkan ulang)

- Platform: **web**.
- Database: **MySQL** (InnoDB, utf8mb4).
- Login **pasien**: **Google OAuth** (Laravel Socialite) + **nomor WA sebagai data profil wajib**, tanpa OTP dulu. Middleware memblokir fitur booking dan QR sampai profil lengkap (halaman Lengkapi Profil).
- Login **staf**: **email + password**, akun dibuat oleh admin. Staf tidak mendaftar sendiri dan tidak memakai Google.
- **QR pasien dibuat otomatis saat pasien terdaftar.** Isi QR hanya **token acak (UUID)** — bukan data identitas, bukan data medis.
- **Multi-profile pasien** (disetujui 27 Okt 2026): satu akun boleh memegang beberapa data diri (diri sendiri, anak, pasangan). `patients.is_utama` menandai data diri yang dipakai untuk kartu QR & booking, `patients.hubungan_keluarga` menjelaskan pemiliknya. `patients.user_id` **tidak** UNIQUE lagi; pembeda antar data diri hanya **`patients.no_rm`** (unik, dibuat otomatis).
- **NIK tidak disimpan** di sistem (dihapus 27 Okt 2026). Jangan menambahkan kolom, field form, atau validasi NIK baru.
- **Surat sakit dikesampingkan** dari scope; jangan ditambahkan kecuali diminta ulang.
- Fitur wajib yang sudah disepakati tim: **chatbot AI** dan **AI Speech** (speech-to-text dan/atau text-to-speech).

## Aturan keamanan dan batasan

- **Chatbot bukan pengganti dokter.** Selalu tampilkan disclaimer pada triase dan saran; keputusan medis tetap di tangan dokter.
- Rekam medis adalah data sensitif: kontrol akses per role, dan catat aksesnya di `activity_logs`.
- Login Google **hanya** untuk role `pasien`. Jangan membuka pendaftaran staf lewat Google.
- Nomor WA belum diverifikasi OTP; verifikasi identitas sebenarnya dilakukan petugas dengan mencocokkan **nama, tanggal lahir, dan alamat** dengan KTP.
- Hasil scan QR hanya boleh diproses pengguna yang sudah login dengan role berwenang; setiap scan dicatat di `qr_scan_logs`.
- Jangan memasukkan data identitas pribadi atau data medis ke dalam QR.
- Jangan menambahkan fitur surat sakit.
- Fitur yang masih berstatus "diusulkan" di `docs/DECISIONS.md` hanya dikerjakan kalau pengguna menyetujuinya.
- **Jangan pernah** menulis kredensial (`.env`, token Google, password DB) ke dalam kode, dokumen, atau pesan commit.

## Konvensi kode

- Ikuti konvensi Laravel: **migrasi** untuk setiap perubahan skema, **Eloquent** untuk akses data, **middleware/policy per role** untuk kontrol akses (sudah ada `PastikanRole`, `PastikanProfilLengkap`), serta **Blade + Tailwind** untuk tampilan.
- **Migrasi yang sudah dijalankan tidak boleh diedit** — buat migrasi baru.
- **Perilaku baru wajib punya feature test** di `tests/Feature/` (pola yang sudah ada: login staf, login Google, profil pasien, multiprofil, panel admin, pemindaian QR). Jalankan suite sebelum menyatakan selesai.
- Harga disalin ke baris transaksi saat transaksi terjadi (`visit_services.harga`, `prescription_items.harga`) — tidak dihitung ulang dari data master.
- Catatan hasil dikte ditandai `medical_records.diinput_via_suara`.
- Data master (poli, dokter, jadwal, tarif, ICD-10, obat) dikelola admin.
- Sebut nama tabel/kolom dengan benar sesuai `klinik_schema.sql` (mis. `patients.no_rm`, `patients.is_utama`, `activity_logs`, `qr_scan_logs`).
- **Route dipisah per wilayah kerja — satu file per modul:** `routes/auth.php`,
  `pasien.php`, `petugas.php`, `dokter.php`, `apoteker.php`, `admin.php` (didaftarkan di
  `bootstrap/app.php`). Tulis route baru di file wilayahnya, **jangan** di `routes/web.php`
  — aturan ini ada supaya beberapa orang bisa bekerja tanpa berebut file yang sama.
- Alias middleware: `role:<nama>` (`PastikanRole`) dan `profil` (`PastikanProfilLengkap`).
  Wilayah pasien memakai prefix nama route `pasien.`, petugas `petugas.`, dan seterusnya.
- Pesan commit: Bahasa Indonesia, satu baris ringkas menjelaskan perubahan (lihat `git log`
  untuk gaya yang sudah dipakai).

## Struktur penting

```
app/Models/                  23 model (Patient, Visit, MedicalRecord, Prescription, Payment, ...)
app/Http/Controllers/        Auth/{LoginController,GoogleController}, Dashboard, Pasien/{Profil,KartuQr}, Petugas/PemindaianQr
app/Http/Middleware/         PastikanRole.php, PastikanProfilLengkap.php
app/Filament/Resources/      panel admin (baru: Users)
app/Providers/Filament/      AdminPanelProvider.php (id + path panel: /admin)
routes/                      web.php (bersama) + satu file per wilayah: auth, pasien,
                             petugas, dokter, apoteker, admin
bootstrap/app.php            registrasi route per modul + alias middleware
database/migrations/         28 migrasi
tests/Feature/               6 feature test
docs/                        dokumen pendukung (lihat tabel di atas)
```

## Perintah penting (Windows + Laragon)

Repo ini dikerjakan di **Windows** dengan **Laragon**. PHP, Composer, dan MySQL tersedia
dari Laragon — **bukan** dari WSL/Linux. Jalankan perintah di **PowerShell/CMD** (atau
terminal di dalam editor), bukan di shell WSL.

```powershell
# prasyarat: buka Laragon -> Start All (Apache/Nginx + MySQL), lalu cd ke root repo

composer install                 # dependensi PHP
composer run setup               # install + .env + app key + migrate + build aset (sekali di awal)
php artisan migrate              # jalankan migrasi baru
php artisan dev                  # server + queue + Vite sekaligus (Laravel 13)
npm run dev                      # kalau hanya butuh Vite
npm run build                    # build aset produksi
php artisan test                 # seluruh test
php artisan test --filter=LoginStafTest   # satu test
php artisan tinker               # eksplor data
```

- **Jangan** menjalankan `migrate:fresh` / `db:wipe` tanpa konfirmasi eksplisit — `klinik_db` berisi data uji yang dipakai untuk demo.
- Kalau MySQL mati, error khasnya `SQLSTATE[HY000] [2002]` → nyalakan Laragon lebih dulu.
- URL penting: panel admin Filament di `/admin`, scan QR petugas di `/petugas/scan`, profil &
  kartu QR pasien di `/profil` dan `/kartu-qr`, dashboard umum di `/dashboard`. Prefix
  `/admin-klinik` disediakan untuk route data master admin (masih kosong).

## Yang harus dilakukan agent di repo ini

1. Baca `AGENTS.md` ini, lalu `docs/DECISIONS.md` sebelum mengusulkan atau mengerjakan fitur.
2. Kalau ada yang tidak jelas: **bertanya, jangan menebak**. Jangan mengarang perintah, nama kolom, atau status fitur.
3. Perubahan skema = migrasi baru. Perilaku baru = feature test baru. Jalankan `php artisan test` sebelum menyatakan pekerjaan selesai.
4. Laporkan apa yang **benar-benar** dijalankan dan hasilnya (angka/keluaran nyata), bukan perkiraan.
5. Fitur di luar scope (surat sakit, NIK, staf lewat Google) tidak dikerjakan walau diminta secara tidak langsung — konfirmasi dulu.
