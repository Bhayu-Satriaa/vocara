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

**TODO — belum final.** Skeleton Laravel 13 sudah ada di folder ini dan `vendor/`
sudah terisi. Yang belum ditetapkan tim:

- koneksi database: `.env` masih `DB_CONNECTION=sqlite`, sedangkan keputusan proyek
  adalah MySQL (lihat `docs/DECISIONS.md`);
- pembuatan database dan pengisian kredensial;
- kredensial Google OAuth untuk login pasien.

## Menjalankan

**TODO — belum final.** Perintah diganti setelah setup disepakati (lihat bagian
"Perintah penting" di `AGENTS.md`). Yang perlu diputuskan tim: perintah migrasi,
seeder data master, menjalankan server lokal, dan test.

## Dokumen

| File | Isi |
|---|---|
| `AGENTS.md` | konteks dan aturan untuk agent/AI |
| `docs/FEATURES.md` | fitur per role, bagian AI & QR, alur utama |
| `docs/DATABASE.md` | kelompok tabel, relasi inti, konvensi data |
| `docs/DECISIONS.md` | keputusan yang sudah final dan yang masih terbuka |
| `klinik_schema.sql` | skema database lengkap |
| `docs/boost-guidelines.md` | panduan setup Laravel Boost bawaan skeleton (referensi tooling) |
