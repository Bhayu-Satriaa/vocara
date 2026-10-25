# Sistem Klinik Kesehatan

Sistem informasi klinik kesehatan **berbasis web** untuk tugas Project Based Learning
(PBL). Aplikasi menangani alur pasien dari registrasi sampai pembayaran, dengan
**chatbot AI** dan **AI Speech** sebagai fitur wajib, ditambah **QR code pasien**
untuk mempercepat registrasi dan check-in.

Stack: **Laravel 13** (PHP) + Blade + **Tailwind CSS**, database **MySQL**
(InnoDB, utf8mb4).

> **Status: tahap dokumen + skeleton Laravel.** Belum ada fitur yang dikoding di luar
> skeleton bawaan Laravel.

## Fitur ringkas

- **Pasien** — login Google, lengkapi profil (nomor WA wajib), QR pribadi, booking
  jadwal dokter, lihat antrean dan riwayat kunjungan, chatbot (FAQ, bantu booking,
  triase awal), bicara ke chatbot dan jawabannya dibacakan.
- **Petugas pendaftaran / kasir** — scan QR untuk check-in, verifikasi identitas
  dengan KTP, daftarkan pasien walk-in, kelola antrean, konfirmasi booking online,
  pembayaran & kwitansi, panggilan antrean lewat suara.
- **Dokter** — daftar pasien hari ini, buka rekam medis lewat QR, input pemeriksaan
  format SOAP + tanda vital, diagnosis ICD-10, resep elektronik, dikte catatan SOAP.
- **Apoteker** — daftar resep dan statusnya, serah obat lewat scan QR, manajemen stok
  obat, cetak etiket.
- **Admin** — manajemen akun dan data master, dashboard statistik, laporan & ekspor
  PDF/Excel, log aktivitas, pengaturan chatbot dan suara.

Rincian per role dan alur utama ada di [`docs/FEATURES.md`](docs/FEATURES.md).

## Instalasi

Yang dibutuhkan: **PHP 8.4 atau lebih baru** (`composer.lock` menuntut `>= 8.4.1`)
dan **MySQL 8**. Di mesin dev ini dipakai PHP 8.5.12 dari Laragon
(`D:\laragon\bin\php\php-8.5.12-Win32-vs17-x64\php.exe`) dan MySQL Laragon.

```bash
composer install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
```

`.env` diisi sendiri oleh masing-masing orang dan **tidak pernah di-commit**:
`DB_CONNECTION=mysql`, `DB_DATABASE=klinik_db`, `DB_USERNAME`, `DB_PASSWORD`, plus
kredensial Google OAuth setelah alur login pasien disetujui tim.

## Menjalankan

```bash
# sekali saja: buat database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS klinik_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php artisan migrate     # 23 tabel aplikasi (+ tabel bawaan Laravel)
php artisan serve       # http://localhost:8000
php artisan test        # pakai SQLite :memory:, tidak butuh MySQL
```

Skema yang sama juga tersedia sebagai SQL mentah — `klinik_schema.sql`
(`mysql -u root < klinik_schema.sql`), berguna kalau ingin melihat atau
menggambar ERD-nya.

## Dokumen

| File | Isi |
|---|---|
| `AGENTS.md` | konteks dan aturan untuk agent/AI |
| `docs/FEATURES.md` | fitur per role, bagian AI & QR, alur utama |
| `docs/DATABASE.md` | kelompok tabel, relasi inti, konvensi data |
| `docs/DECISIONS.md` | keputusan yang sudah final dan yang masih terbuka |
| `klinik_schema.sql` | skema database lengkap |
| `docs/boost-guidelines.md` | panduan setup Laravel Boost bawaan skeleton (referensi tooling) |
