# Database

- Database: **MySQL**, engine **InnoDB**, charset **utf8mb4**.
- Skema lengkap: **`klinik_schema.sql`** (26 tabel, lengkap dengan foreign key).
- Dokumen ini hanya ringkasan. **Jangan menyalin/menulis ulang definisi tabel dari
  sini** — kalau butuh kolom, tipe, atau constraint, baca `klinik_schema.sql`.

## Kelompok tabel

- **Akun:** `users`
- **Pasien dan QR:** `patients`, `qr_scan_logs`
- **Dokter:** `polis`, `doctors`, `doctor_schedules`
- **Kunjungan:** `visits`
- **Rekam medis:** `medical_records`, `diseases`, `medical_record_diagnoses`
- **Layanan:** `services`, `visit_services`
- **Farmasi:** `medicines`, `medicine_stock_movements`, `prescriptions`,
  `prescription_items`
- **Pembayaran:** `payments`
- **Chatbot dan speech:** `chatbot_knowledge`, `chat_conversations`, `chat_messages`,
  `triage_results`, `speech_logs`
- **Audit:** `activity_logs`

Tabel bawaan Laravel (`sessions`, `password_reset_tokens`, `jobs`, `cache`,
`notifications`) **tidak** ditulis di `klinik_schema.sql` karena dibuat lewat migrasi.

## Relasi inti

- Kunjungan (`visits`) adalah titik temu pasien dengan dokter/jadwal; status kunjungan
  disimpan di `visits.status` (`booking → menunggu → diperiksa → farmasi →
  pembayaran → selesai`, atau `batal`).
- `patients.user_id` menunjuk ke akun `users` dan **boleh kosong** untuk pasien walk-in
  yang didaftarkan petugas.
- Rekam medis tersimpan per kunjungan (`medical_records`); diagnosis disimpan terpisah
  di `medical_record_diagnoses` dan merujuk ke `diseases`.
- Layanan yang dikerjakan pada satu kunjungan ada di `visit_services`; resep pada satu
  kunjungan ada di `prescriptions` + `prescription_items`.
- Pergerakan stok obat dicatat di `medicine_stock_movements`.
- Setiap scan QR tercatat di `qr_scan_logs`; aktivitas lain (termasuk akses rekam medis)
  di `activity_logs`.
- Percakapan chatbot: `chat_conversations` → `chat_messages`; hasil triase di
  `triage_results`; log suara di `speech_logs`.

Arah relasi di atas mengikuti penamaan tabel; nama kolom foreign key yang tepat
**verifikasi di `klinik_schema.sql`** — jangan mengarang nama kolom.

## Konvensi data

- **Token QR** disimpan di `patients.qr_token` dan berupa **UUID acak**. Jangan
  menyimpan NIK atau data medis di QR.
- **Harga disalin** ke `visit_services.harga` dan `prescription_items.harga` saat
  transaksi terjadi — bukan hasil hitung ulang dari data master.
- Catatan pemeriksaan hasil dikte ditandai **`medical_records.diinput_via_suara`**.
- Verifikasi identitas pasien dicatat lewat status QR (`belum` → `terverifikasi`)
  setelah petugas mencocokkan KTP; nomor WA belum diverifikasi OTP.

## ERD

ERD bisa dibuat dengan menempelkan isi `klinik_schema.sql` ke ChartDB (chartdb.io),
dbdiagram.io, atau MySQL Workbench (Reverse Engineer).
