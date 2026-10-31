# Database

- Database: **MySQL**, engine **InnoDB**, charset **utf8mb4**.
- Skema lengkap: **`klinik_schema.sql`** (23 tabel, lengkap dengan foreign key).
- Dokumen ini hanya ringkasan. **Jangan menyalin/menulis ulang definisi tabel dari
  sini** — kalau butuh kolom, tipe, atau constraint, baca `klinik_schema.sql`.

## Kelompok tabel

- **Akun:** `users`
- **Pasien dan QR:** `patients`, `qr_scan_logs`
- **Dokter:** `polis`, `doctors`, `doctor_schedules`
- **Kunjungan:** `visits`
- **Rekam medis:** `medical_records`, `diseases`, `medical_record_diagnoses`
- **Layanan:** `services`, `visit_services`
- **Farmasi:** `prescriptions`, `prescription_items`; `medicines` dan
  `medicine_stock_movements` **tidak dipakai fitur saat ini** (lihat "Konvensi data")
- **Pembayaran:** `payments` (status + metode, diubah admin; tanpa payment gateway)
- **Chatbot dan speech:** `chatbot_knowledge`, `chat_conversations`, `chat_messages`,
  `triage_results`, `speech_logs`
- **Audit:** `activity_logs`

Tabel bawaan Laravel (`sessions`, `password_reset_tokens`, `jobs`, `cache`,
`notifications`) **tidak** ditulis di `klinik_schema.sql` karena dibuat lewat migrasi.

## Relasi inti

- Kunjungan (`visits`) adalah titik temu pasien dengan dokter/jadwal; status kunjungan
  disimpan di `visits.status` (`booking → menunggu → diperiksa → farmasi →
  pembayaran → selesai`, atau `batal`). Perpindahan `pembayaran → selesai` terjadi saat
  admin menandai `payments.status` menjadi `lunas`.
- `patients.user_id` menunjuk ke akun `users` dan **boleh kosong** untuk pasien walk-in
  yang didaftarkan petugas. Satu akun pasien boleh memegang **lebih dari satu data
  diri** (multi-profile: diri sendiri, anak, pasangan); tiap data diri punya `no_rm`
  dan `qr_token` sendiri, jadi rekam medisnya tetap terpisah.
- Rekam medis tersimpan per kunjungan (`medical_records`); diagnosis disimpan terpisah
  di `medical_record_diagnoses` dan merujuk ke `diseases`.
- Layanan yang dikerjakan pada satu kunjungan ada di `visit_services`; resep pada satu
  kunjungan ada di `prescriptions` + `prescription_items`.
- `medicine_stock_movements` tidak dipakai: manajemen stok obat dihapus sementara
  (lihat `docs/DECISIONS.md`).
- Setiap scan QR tercatat di `qr_scan_logs`; aktivitas lain (termasuk akses rekam medis)
  di `activity_logs`.
- Percakapan chatbot: `chat_conversations` → `chat_messages`; hasil triase di
  `triage_results`; log suara di `speech_logs`.

Arah relasi di atas mengikuti penamaan tabel; nama kolom foreign key yang tepat
**verifikasi di `klinik_schema.sql`** — jangan mengarang nama kolom.

## Konvensi data

- **Token QR** disimpan di `patients.qr_token` dan berupa **UUID acak**. Jangan
  menyimpan data identitas atau data medis di QR.
- **Multi-profile**: `patients.is_utama` (TINYINT 1) menandai data diri yang sedang
  dipakai akun untuk kartu QR dan booking — **hanya satu** per akun, dijaga di
  aplikasi (`Patient::jadikanUtama()`), bukan oleh UNIQUE database. `patients.user_id`
  **tidak** UNIQUE lagi (indeks biasa `idx_patients_user`). **NIK tidak disimpan**
  (kolom `patients.nik` dihapus); pembeda antar data diri hanya `patients.no_rm`
  (unik, NOT NULL, dibuat otomatis oleh model). `patients.hubungan_keluarga`
  menjelaskan pemilik data diri (`diri sendiri`, `pasangan`, `anak`, `orang tua`,
  `saudara`, `lainnya`).
- **Slot jam kunjungan** disimpan di `visits.jam`. Satu dokter tidak boleh punya dua
  kunjungan **aktif** di tanggal + jam yang sama: dijaga `UNIQUE uq_slot_dokter`
  (`doctor_id`, `tgl_kunjungan`, `jam`, `slot_aktif`). Kolom generated `slot_aktif`
  bernilai NULL kalau status `batal`, sehingga slot bekas batal bisa dipesan ulang.
  Pilihan jam dihitung backend dari `doctor_schedules.jam_mulai`, `jam_selesai`, dan
  `durasi_slot`.
- **Harga disalin** ke `visit_services.harga` dan `prescription_items.harga` saat
  transaksi terjadi — bukan hasil hitung ulang dari data master.
- **Pembayaran = status, bukan integrasi.** `payments.status` bernilai `belum_lunas`
  atau `lunas`, `payments.metode` bernilai `transfer` atau `cash` — diubah (toggle)
  oleh **admin**, bukan kasir, dan dicatat di `activity_logs`. Tidak ada payment
  gateway, verifikasi otomatis, atau pembayaran online. Kwitansi dicetak dari
  `payments.nomor_kwitansi`.
- **Resep** disimpan sebagai teks bebas dari dokter (nama obat, dosis, aturan pakai).
  `medicines` dan `prescription_items.medicine_id` tidak dipakai fitur saat ini — skema
  masih mewajibkan `medicine_id` NOT NULL + foreign key, jadi migrasi perlu dibuat kalau
  nanti diaktifkan.
- **Tanpa BPJS/asuransi**: aplikasi tidak mencatat data BPJS atau asuransi lain.
- Catatan pemeriksaan hasil dikte ditandai **`medical_records.diinput_via_suara`**.
- Verifikasi identitas pasien dicatat lewat status QR (`belum` → `terverifikasi`)
  setelah petugas mencocokkan **nama, tanggal lahir, dan alamat** dengan KTP; nomor WA
  belum diverifikasi OTP. NIK tidak dipakai lagi.

## NIK dihapus (27 Okt 2026)

Kolom `patients.nik` beserta unique index-nya sudah dihapus (migrasi
`2026_10_27_1000025_hapus_nik_dari_patients`). **Jangan menambahkan kembali** kolom,
field form, atau validasi NIK.

Nilai NIK lama disimpan sementara di tabel `patients_nik_backup` (dibuat manual sebelum
migrasi) — hapus tabel itu setelah perubahan dinyatakan aman:

```sql
-- cek isi backup
SELECT * FROM patients_nik_backup;
-- hapus backup kalau sudah tidak diperlukan
DROP TABLE patients_nik_backup;
```

Kalau migrasi di-rollback (`php artisan migrate:rollback`), kolom `nik` kembali sebagai
**nullable** dan isinya harus dipulihkan manual:

```sql
UPDATE patients p JOIN patients_nik_backup b ON b.id = p.id SET p.nik = b.nik;
ALTER TABLE patients MODIFY nik CHAR(16) NOT NULL;   -- opsional, setelah semua terisi
```

## ERD

ERD bisa dibuat dengan menempelkan isi `klinik_schema.sql` ke ChartDB (chartdb.io),
dbdiagram.io, atau MySQL Workbench (Reverse Engineer).
