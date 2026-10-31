# Daftar Fitur — Sistem Klinik Kesehatan (PBL)

Ringkasan fitur yang **sudah direncanakan/diputuskan tim** per tanggal terakhir
diperbarui, untuk dibahas bersama dosen pembimbing. Isinya dirangkum dari
[`FEATURES.md`](FEATURES.md) (fitur per role) dan [`DECISIONS.md`](DECISIONS.md)
(keputusan final + yang masih terbuka), jadi tidak ada fitur baru yang dikarang di
dokumen ini.

**Bentuk:** aplikasi **web**. **Stack:** Laravel 13 + MySQL + Tailwind CSS.
**Fitur wajib yang disepakati tim:** **Chatbot AI** dan **AI Speech** (speech-to-text
dan/atau text-to-speech), ditambah **QR code pasien**.

Keterangan status:

- **✅ Sudah jadi** — sudah ada kodenya dan sudah diuji.
- **🔜 Direncanakan** — sudah masuk daftar, belum dikerjakan.
- **❔ Belum diputuskan** — pernah diusulkan, belum disetujui; tidak dihitung sebagai scope.

## 1. Fitur wajib

| Fitur | Rincian pemakaian | Status | Pemilik |
|---|---|---|---|
| Chatbot AI | Pasien: FAQ klinik, bantu booking, triase awal + saran poli. Admin: kelola basis pengetahuan + lihat riwayat percakapan. Dokter: melihat ringkasan triase sebelum pasien masuk | 🔜 Direncanakan | Jian |
| AI Speech — TTS | Panggilan nomor antrean oleh petugas; pembacaan jawaban chatbot untuk pasien | 🔜 Direncanakan | Jian |
| AI Speech — STT | Dikte catatan SOAP oleh dokter; pasien berbicara ke chatbot | 🔜 Direncanakan | Jian |
| QR code pasien | Registrasi & check-in, buka rekam medis (dokter), serah obat (apoteker) | ✅ Sudah jadi (kartu QR + scan petugas + verifikasi KTP) | Jian (modul petugas) |

Aturan mengikat: **chatbot bukan pengganti dokter** — disclaimer wajib tampil pada
triase dan saran, keputusan medis tetap di tangan dokter.

## 2. Fitur inti per role

### Pasien

| Fitur | Status |
|---|---|
| Login Google (OAuth) + lengkapi profil, nomor WA wajib | ✅ Sudah jadi |
| Nomor rekam medis otomatis (`RM-000001`) | ✅ Sudah jadi |
| QR pribadi (gambar QR + cetak) dengan status belum/terverifikasi | ✅ Sudah jadi |
| Multi profil: satu akun untuk beberapa anggota keluarga | 🔜 Direncanakan (Ridwan) |
| Booking jadwal dokter (pilih jam dari slot dokter) | 🔜 Direncanakan (Nabil) |
| Booking untuk anggota keluarga lain | 🔜 Direncanakan (Ridwan) |
| Lihat antrean, riwayat kunjungan, resep, tagihan | 🔜 Direncanakan |
| Chatbot (FAQ, bantu booking, triase awal) + bicara ke chatbot | 🔜 Direncanakan (Jian) |

### Petugas pendaftaran

| Fitur | Status |
|---|---|
| Scan QR pasien untuk check-in, setiap scan tercatat | ✅ Sudah jadi (pencatatan) / 🔜 antrean |
| Verifikasi identitas: cocokkan KTP → status `terverifikasi` | ✅ Sudah jadi |
| Daftarkan pasien walk-in (QR otomatis) | 🔜 Direncanakan |
| Pencarian manual pasien kalau QR hilang | 🔜 Direncanakan |
| Kelola antrean + konfirmasi booking online + nomor antrean | 🔜 Direncanakan |
| Panggilan antrean lewat suara (TTS) | 🔜 Direncanakan (Jian) |

Pembayaran dan kwitansi **sementara** ditangani admin, bukan petugas.

### Dokter

| Fitur | Status |
|---|---|
| Daftar pasien hari ini | 🔜 Direncanakan |
| Buka rekam medis lewat scan QR | 🔜 Direncanakan |
| Riwayat rekam medis pasien | 🔜 Direncanakan |
| Input pemeriksaan format SOAP + tanda vital | 🔜 Direncanakan |
| Diagnosis memakai ICD-10 | 🔜 Direncanakan |
| Resep elektronik (teks bebas: nama obat, dosis, aturan pakai) | 🔜 Direncanakan |
| Dikte catatan SOAP (STT) + ringkasan triase chatbot | 🔜 Direncanakan (Jian) |

### Apoteker

| Fitur | Status |
|---|---|
| Daftar resep masuk berisi obat dari resep dokter (teks) + status (menunggu/disiapkan/selesai) | 🔜 Direncanakan |
| Scan QR saat penyerahan obat | 🔜 Direncanakan |
| Cetak etiket | 🔜 Direncanakan |

**Tanpa manajemen stok** (dihapus sementara): stok masuk, keluar, kedaluwarsa, dan stok
minimum tidak dicatat. Konsekuensinya apoteker tidak dapat peringatan kalau obat
sebenarnya sudah habis. Ini penundaan, bukan pembatalan permanen.

### Admin

| Fitur | Status |
|---|---|
| Manajemen akun semua role (panel Filament di `/admin`) | ✅ Sudah jadi (akun staf) |
| Data master: poli, dokter, jadwal, tarif, ICD-10 | ✅ Sudah jadi (lewat seeder) / 🔜 CRUD lewat panel |
| Dashboard statistik: kunjungan, penyakit terbanyak | 🔜 Direncanakan |
| Pembayaran: toggle status (lunas / belum lunas) + metode (transfer / cash) + cetak kwitansi | 🔜 Direncanakan |
| Laporan dan ekspor PDF/Excel | 🔜 Direncanakan |
| Log aktivitas (scan QR, akses rekam medis) | ✅ Sudah jadi |
| Kelola basis pengetahuan chatbot + pengaturan suara | 🔜 Direncanakan (Jian) |

### Lintas role

- 🔜 Notifikasi/pengingat jadwal dan resep siap.
- ✅ Kontrol akses berbasis role (middleware per role; pasien tidak bisa membuka
  halaman staf, staf tidak bisa membuka halaman pasien). Ubah status pembayaran hanya
  untuk admin.

## 3. Alur utama yang sudah ditetapkan

- **Alur login pasien:** login Google → akun `pasien` dibuat otomatis → wajib
  lengkapi profil (nomor WA) → QR dibuat otomatis berstatus `belum` → saat datang
  pertama petugas mencocokkan KTP → status `terverifikasi`.
- **Alur QR:** QR berisi **token acak UUID** (`patients.qr_token`), bukan NIK atau
  data medis. Petugas memindai di `/petugas/scan` → data pasien muncul → tombol
  verifikasi KTP. Setiap pemindaian dicatat di `qr_scan_logs`, setiap tindakan di
  `activity_logs`.
- **Alur status kunjungan:** `booking → menunggu → diperiksa → farmasi → pembayaran
  → selesai` (atau `batal`). Tahap `pembayaran` tetap ada, tetapi perpindahannya
  ditentukan oleh **status pembayaran yang di-toggle admin**: kunjungan menjadi
  `selesai` setelah admin menandai pembayaran `lunas`. Tidak ada payment gateway
  atau verifikasi otomatis.

## 4. Yang sudah jadi dan bisa didemokan sekarang

- Skema **23 tabel** aplikasi (31 tabel di database bersama 8 tabel bawaan Laravel),
  **28 migrasi**, **37 foreign key** — sudah dijalankan di database `klinik_db`.
- **Anti double-booking dijaga database** (bukan pengecekan di aplikasi):
  `UNIQUE uq_slot_dokter (doctor_id, tgl_kunjungan, jam, slot_aktif)`; kunjungan
  batal otomatis membebaskan slotnya.
- **23 model Eloquent** + seeder data master: 3 poli, 3 dokter, 15 jadwal praktik
  (slot 30 menit), 6 layanan, 5 obat, 8 kode ICD-10, akun staf demo.
- **Login staf multi-role** (email + password buatan admin) + dashboard per role +
  middleware pembatas role.
- **Login pasien lewat Google** + halaman Lengkapi Profil + nomor rekam medis
  otomatis.
- **Panel admin Filament v5** di `/admin` (manajemen akun staf, dibatasi per role,
  perubahan akun tercatat di log).
- **Kartu QR pasien** dengan gambar QR (SVG) + tombol cetak.
- **Scan QR + verifikasi KTP petugas** di `/petugas/scan`.
- **Multi profil pasien** — satu akun memegang beberapa data diri, masing-masing
  bernomor RM dan QR sendiri, dengan pemilihan profil aktif di `/profil`.
- **53 test otomatis lulus (205 assertion)** — mencakup login staf, panel admin,
  login Google pasien, profil pasien, pemindaian QR, dan multi profil.

## 5. Keputusan teknis yang sudah final

- **Platform:** web. **Database:** MySQL (InnoDB, utf8mb4) — skema lengkap di
  `klinik_schema.sql`.
- **Suara:** **Gemini Live API native audio** (speech-to-speech, bukan rantai
  STT→LLM→TTS), model `gemini-2.5-flash-native-audio-preview-12-2025`, suara `Kore`.
  Cadangan bebas kuota: TTS/STT bawaan browser + Groq Whisper untuk dikte.
- **Otak chatbot (teks):** model dari **9Router lokal** (endpoint OpenAI-compatible
  `http://127.0.0.1:20128/v1`), alternatif **Command Code Provider API**.
- **Pemilihan model berdasarkan pengujian**, bukan perkiraan: 12 model diuji 3
  skenario; model tercepat 1,8–2,1 detik, dan satu model terbukti **langsung
  membuat booking tanpa meminta konfirmasi** sehingga tidak dipakai sebagai otak
  agent.
- **Keamanan & etika data:** kunci API penyedia tidak pernah sampai ke browser
  (server yang membuat token sesi) · demo memakai data sintetis · rekam medis hanya
  untuk role berwenang dan setiap akses dicatat · QR tidak memuat NIK/data medis.

## 6. Pembagian tugas

| Anggota | Wilayah |
|---|---|
| Jian | Chatbot AI + AI speech |
| Juli | Design web keseluruhan |
| Ridwan | Multi profil (1 akun banyak biodata, mirip Mobile JKN) |
| Nabil | Bantu design + janji temu booking |
| Royan | Belum dikonfirmasi |

**Belum ada pemiliknya** (perlu keputusan): antrean & check-in petugas · pemeriksaan
dokter (SOAP) · farmasi (serah obat) · pembayaran & kwitansi (admin) · data master +
statistik admin.

## 7. Masih terbuka — bahan diskusi dengan dospem

1. **Jumlah anggota final dan batas waktu** pengerjaan (menahan pembagian tugas).
2. **Apakah scope seluas ini realistis** atau perlu dipotong. Kandidat potong:
   farmasi, laporan PDF/Excel.
3. ✅ **Multi profil** (1 akun banyak biodata) — sudah dikerjakan: UNIQUE
   `patients.user_id` dilepas, ditambah `patients.is_utama` + `patients.hubungan_keluarga`,
   profil aktif dipakai kartu QR dan booking. Satu `is_utama` per akun dijaga aplikasi
   (`Patient::jadikanUtama()`), bukan UNIQUE database.
4. **AI speech: satu arah (TTS) saja, atau dua arah (STT + TTS)?**
5. `nik` **dihapus** dari `patients` (migrasi `2026_10_27_1000025`) — pembeda antar data
   diri hanya `no_rm`. Sisa aturan yang masih terbuka: `no_wa` tetap wajib untuk pasien
   yang mendaftar sendiri.
6. **Kapan nomor antrean dibuat**: saat booking atau saat check-in.
7. **Format nomor rekam medis** `RM-` + 6 angka (usulan, perlu konfirmasi).
8. **Kuota/biaya AI**: sementara memakai free tier Gemini (kuota model suara ketat);
   perlu keputusan apakah memakai anggaran/billing.
9. **Fitur usulan yang belum disetujui** (jangan dianggap scope): ringkasan rekam
   medis otomatis, pengingat otomatis email/WA, grafik tanda vital, telekonsultasi
   chat, penjelasan resep awam, consent pasien untuk AI.
   **Surat sakit sudah dikesampingkan** oleh tim. **BPJS/asuransi tidak dipakai.**
   **Manajemen stok obat ditunda sementara** (bukan dibatalkan).
10. **10 tabel** belum punya `created_at`/`updated_at` (sementara dimatikan di model)
    — perlu keputusan apakah diseragamkan.

## 8. Urutan pengerjaan yang disepakati

1. ✅ Skema, model, seeder, login staf, panel admin, login pasien + QR.
2. ✅ Scan QR + verifikasi KTP (modul petugas, bagian QR).
3. ✅ Multi profil (selesai — 2 migrasi + `MultiprofilPasienTest`).
4. 🔜 Janji temu / booking kunjungan → antrean & check-in.
5. 🔜 Pemeriksaan dokter (SOAP, ICD-10, resep) → farmasi (serah obat) → pembayaran &
   kwitansi (admin). Manajemen stok obat ditunda, bukan dibatalkan.
6. 🔜 Data master + statistik admin.
7. 🔜 **Terakhir: chatbot AI, lalu AI Speech** (fitur wajib, ditunjukkan di demo akhir).

Penanda demo (tag git): `v0.1-scan-qr` → `v0.2-multiprofil` → `v0.3-janji-temu` →
`v0.4-design-hangat` → `v1.0-chatbot-speech`.

## 9. Cara menjalankan (untuk demo di depan dospem)

```bash
composer install
cp .env.example .env && php artisan key:generate   # isi DB + kredensial Google sendiri
npm install && npm run build
php artisan migrate:fresh --seed
php artisan filament:assets
php artisan serve --host=0.0.0.0 --port=8000
```

Akun demo (password semuanya `password`): `admin@klinik.test`, `petugas@klinik.test`,
`apoteker@klinik.test`, `dokter.ayu@klinik.test`. Halaman pasien `/`, panel admin
`/admin`, scan QR petugas `/petugas/scan`.
