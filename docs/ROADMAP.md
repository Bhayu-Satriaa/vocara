# Roadmap Alur Pengembangan Fitur

Urutan pengerjaan fitur Sistem Klinik Kesehatan: fase, isi teknis, prasyarat, definisi
selesai, dan **titik demo** di tiap fase. Dipakai supaya semua anggota tahu apa yang
dikerjakan berikutnya dan apa yang menunggu dirinya.

Sumber: [`FEATURES.md`](FEATURES.md) (fitur per role), [`DECISIONS.md`](DECISIONS.md)
(keputusan final), [`DAFTAR-FITUR.md`](DAFTAR-FITUR.md) (daftar fitur + status), dan
skema di `klinik_schema.sql`.

**Sinkronisasi (31 Okt 2026):** branch ini sudah di-merge dengan `main` (33 commit). Kalau
isi roadmap bertabrakan dengan `main`, **aturan `main` yang dipakai**: pembayaran = toggle
status oleh admin, resep dokter = teks bebas, manajemen stok obat ditunda, NIK dihapus.

## Cara memakai dokumen ini

- Satu fase = beberapa PR kecil (bukan satu PR raksasa). Aturan branch/PR ada di
  [`BRANCHING.md`](BRANCHING.md).
- **Satu fitur tuntas dulu** (migrasi → model → controller → route → view → test →
  catat di dokumen), baru pindah fase.
- Angka perkiraan di dokumen ini **perkiraan kasar**, bukan janji — dipakai untuk
  menyusun urutan, bukan untuk menagih.
- Fase yang isinya "belum ada pemilik" **tidak akan jalan sendiri**: perlu keputusan
  pembagian tugas lebih dulu.

## Definisi selesai (berlaku untuk setiap fase)

1. Migrasi (kalau ada) jalan di database kosong: `php artisan migrate:fresh --seed`.
2. `php artisan test` lulus, dan **jumlah test bertambah** — baseline `main` terukur
   **59 test (253 assertion)** dan tidak boleh turun.
3. Route baru dibatasi role (`middleware('role:...')`); halaman pasien di balik
   `middleware('profil')`.
4. Data sensitif (rekam medis, QR, pembayaran) menulis jejak ke `activity_logs`.
5. Unik/anti-bentrok dijaga **database**, bukan pengecekan `if` di aplikasi.
6. `docs/DECISIONS.md` dan `README.md` diperbarui kalau ada keputusan/cara pakai baru.
7. PR di-approve dan di-merge ke `main`.

## Peta ketergantungan

```
FASE 0 (fondasi) ✅
   └── FASE 1 multi profil (Ridwan) ✅ ──┐        [Juli: token design, paralel]
                                       ├── FASE 2 janji temu/booking (Nabil)
                                       │        └── FASE 3 antrean & check-in petugas
                                       │                 └── FASE 4 pemeriksaan dokter (SOAP)
                                       │                          └── FASE 5 farmasi (serah obat, tanpa stok)
                                       │                                   └── FASE 6 pembayaran & kwitansi (admin)
                                       └── FASE 4 juga membuka jalan chatbot (triase)
FASE 7 admin (data master + statistik) — bisa kapan saja setelah FASE 4 punya data
FASE 8 chatbot AI (Jian) ──► FASE 9 AI speech (Jian) ──► FASE 10 finalisasi & demo
```

**Jalur kritis** = FASE 1 → 2 → 3. Kalau fase ini lambat, semua fase lain ikut mundur.
Juli (design) berjalan sejajar, tidak memblokir siapa pun selama token design
ditetapkan lebih dulu.

---

## FASE 0 — Fondasi ✅ (selesai)

Skema 23 tabel + migrasi (29 migrasi, 37 foreign key), 23 model Eloquent, seeder data
master, login staf multi-role, login Google pasien + Lengkapi Profil, nomor RM otomatis,
panel admin Filament, kartu QR dengan gambar QR, **scan QR + verifikasi KTP petugas**,
aturan branch & PR, daftar fitur. **42 test lulus (143 assertion).**

*Titik demo:* tag `v0.1-scan-qr`.

## FASE 1 — Multi profil (1 akun, banyak biodata) — **Ridwan**, dibarengi design — **Juli**

**Status: ✅ selesai di `main` (27 Okt 2026).** Migrasi
`2026_10_27_1000024_ubah_patients_untuk_multiprofil` + `2026_10_27_1000025_hapus_nik_dari_patients`,
13 test `MultiprofilPasienTest` + `PersetujuanProfilTest` (pengajuan data diri lewat admin).
Terukur di branch ini 31 Okt 2026: **59 test lulus (253 assertion), 29 migrasi**. Sisa: seeder akun demo
≥2 data diri, lalu tag `v0.2-multiprofil`.

**Tujuan:** satu akun Google bisa menampung beberapa anggota keluarga; tiap profil punya
identitas, nomor RM, dan QR sendiri (mirip fitur "keluarga" di Mobile JKN).

**Prasyarat:** FASE 0.

**Isi teknis (yang benar-benar dikerjakan):**
- Migrasi baru (jangan edit migrasi lama):
  - lepas `UNIQUE` pada `patients.user_id` → `$table->dropUnique(['user_id'])`, diganti indeks
    `idx_patients_user` (InnoDB butuh indeks berkolom pertama `user_id` untuk `fk_patients_user`);
  - tambah `patients.is_utama` (boolean, satu per akun) dan `patients.hubungan_keluarga`
    (enum: diri sendiri/pasangan/anak/orang tua/saudara/lainnya);
  - `kepala_keluarga_id` **tidak dibuat** — belum ada kebutuhan yang menuntut hierarki itu.
- **NIK dihapus dari sistem** (keputusan tim 27 Okt 2026, dicatat di `DECISIONS.md`), jadi pembeda
  antar data diri hanya `patients.no_rm` (NOT NULL + UNIQUE, diisi otomatis oleh model). Aturan
  "NIK unik per profil" karena itu tidak berlaku lagi.
- **"Profil aktif" disimpan di database** (`patients.is_utama`), bukan di session: kartu QR,
  booking, dan riwayat membaca `User::profilAktif()`. Satu `is_utama` per akun dijaga aplikasi
  (`Patient::jadikanUtama()`), bukan UNIQUE database — MySQL tidak punya partial unique index.
- Route di `routes/pasien.php`: daftar profil, tambah profil, ubah profil, ganti profil aktif.
- **Data diri baru wajib disetujui admin**: form pasien menulis ke `patient_profile_requests`
  (`status` menunggu/disetujui/ditolak), nomor RM + QR baru terbit setelah admin menyetujui
  di panel `/admin-klinik/persetujuan-profil`. Ubah data diri yang sudah ada tetap langsung
  tersimpan.
- Titik kode yang dulu mengasumsikan satu pasien per akun sudah ikut: `User::profilLengkap()`
  (sekarang artinya "ada data diri yang bernomor WA"), `PastikanProfilLengkap`, `GoogleController`
  (arahkan ke halaman profil kalau belum lengkap), `ProfilController`, `KartuQrController`
  (pakai profil aktif, `firstOrFail` hilang), `DashboardController`, dan
  `SimpanProfilPasienRequest` (kepemilikan data diri dicek dari akun yang login).
- Test baru: satu akun dua data diri; data diri baru tidak menggeser profil aktif; pilih profil
  aktif; tiap data diri punya `no_rm` dan `qr_token` sendiri; kartu QR menampilkan profil aktif;
  data diri milik akun lain ditolak (403); hubungan keluarga wajib dipilih; kolom `nik` sudah
  tidak ada di skema.

**Definisi selesai:** semua test lulus, `docs/DECISIONS.md` mencatat perubahan skema ini.
Menambah akun demo ≥2 profil (seeder) masih terbuka.

*Titik demo:* tag `v0.2-multiprofil`.

**Juli (paralel, tidak memblokir):** token design Hangat di `resources/css/app.css`
(teal `#0D9488`, orange `#EA580C`, cream `#FFF8F2`, font Varela Round + Nunito Sans),
komponen bersama (tombol, kartu, tabel, form, badge status), lalu halaman per halaman
(login → beranda pasien → kartu QR → panel admin). Font **di-self-host** supaya demo di
Wi-Fi kampus tidak kehilangan tampilan.

## FASE 2 — Janji temu / booking — **Nabil**

**Prasyarat:** FASE 1 (harus jelas profil siapa yang booking).

**Isi teknis:**
- **`BookingService` sebagai satu-satunya pintu booking** — web, chatbot, dan suara
  memanggil layanan yang sama. Tidak ada jalur booking kedua.
- Route pasien: pilih poli → dokter → tanggal → **pilihan jam dihitung backend** dari
  `doctor_schedules.jam_mulai`, `jam_selesai`, `durasi_slot` (mana yang sudah terisi
  disaring dari database). Nama hari **dihitung backend**, bukan oleh model AI.
- Menulis `visits`: `patient_id`, `doctor_id`, `poli_id`, `schedule_id`, `tgl_kunjungan`,
  `jam`, `status='booking'`, `sumber='online'`, `keluhan_awal`.
- **Anti double-booking mengandalkan constraint** `uq_slot_dokter (doctor_id,
  tgl_kunjungan, jam, slot_aktif)`: tangkap pelanggaran constraint → pesan ramah
  "jam itu baru saja diambil orang lain". Jangan cek `if exists` lalu insert.
- Batal janji: `status='batal'` (slot otomatis bebas karena `slot_aktif` jadi NULL).
- Halaman "janji saya": daftar booking per profil aktif + tombol batal. Boleh untuk
  data diri keluarga mana pun milik akun (pilih dulu), bukan cuma yang sedang aktif.
- Riwayat kunjungan, resep, dan tagihan pasien: tagihan **hanya dilihat** (status
  `lunas`/`belum_lunas` + metode `transfer`/`cash`), tidak bisa diubah pasien.
- Test: dua booking slot sama → gagal; setelah batal → slot bisa dipesan lagi; jam di luar
  jadwal dokter ditolak; pasien tidak bisa booking atas nama profil orang lain.

*Titik demo:* tag `v0.3-janji-temu` — pasien booking, barisnya muncul di database dan di
layar petugas.

## FASE 3 — Antrean & check-in petugas — **belum ada pemilik**

**Prasyarat:** FASE 2 (harus ada kunjungan untuk diantrikan).

**Isi teknis:**
- `/petugas/antrean`: daftar kunjungan hari ini (filter tanggal/poli/dokter/status,
  cari pasien), urutan sesuai nomor antrean.
- **Check-in**: isi `no_antrian` (dihitung per poli+dokter+tanggal), `checkin_metode`
  (`qr`/`manual`), `checkin_at`, `checkin_by`, status `booking → menunggu`.
  Perhatikan `uq_antrian (poli_id, doctor_id, tgl_kunjungan, no_antrian)`.
- **Menyambung modul scan QR yang sudah ada**: setelah petugas memindai kartu QR, kalau
  pasien punya kunjungan `booking` hari ini → tawarkan tombol "check-in sekarang"
  (catat `qr_scan_logs` konteks `checkin`).
- **Pendaftaran walk-in**: petugas mendaftarkan pasien langsung (nomor RM + QR otomatis),
  plus **pencarian manual** kalau QR hilang — `patients.user_id` boleh kosong.
- Keputusan tim belum diambil: nomor antrean dibuat **saat check-in** (usulan) atau
  saat booking.

*Titik demo:* pasien booking → petugas scan QR → pasien masuk antrean nomor 1.

## FASE 4 — Pemeriksaan dokter (SOAP) — **belum ada pemilik**

**Prasyarat:** FASE 3.

**Isi teknis:**
- `/dokter/pasien`: daftar pasien hari ini berstatus `menunggu`; buka rekam medis lewat
  scan QR (konteks `pemeriksaan` di `qr_scan_logs`) atau tombol.
- Form SOAP + tanda vital → `medical_records`: `subjective`, `objective`, `assessment`,
  `plan`, `tekanan_darah`, `suhu`, `nadi`, `napas`, `berat_badan`, `tinggi_badan`.
- Diagnosis ICD-10 → `medical_record_diagnoses` (bisa lebih dari satu).
- Resep elektronik **teks bebas** (nama obat, dosis, aturan pakai) → `prescriptions` +
  `prescription_items`, **tidak memilih dari `medicines`**. Catatan teknis: `medicine_id`
  masih NOT NULL + FK, migrasi pelepasnya harus dibuat sebelum fase ini jalan.
- Akhir pemeriksaan: `visits.status = 'farmasi'`; akses rekam medis dicatat di `activity_logs`.
- Kolom `diinput_via_suara` disiapkan untuk FASE 9 (dikte).

*Titik demo:* satu kunjungan lengkap: menunggu → diperiksa (SOAP + ICD-10) → resep keluar.

## FASE 5 — Farmasi (serah obat) — **belum ada pemilik**

**Prasyarat:** FASE 4.

**Isi teknis:**
- `/apoteker/resep`: daftar resep (`menunggu`/`disiapkan`/`selesai`), detail item, tombol
  siapkan → serahkan.
- Serah obat: scan QR pasien (konteks `farmasi`), isi `apoteker_id`, `diserahkan_at`,
  `status='selesai'`.
- **Tanpa manajemen stok** (dihapus sementara — `DECISIONS.md`): tidak ada
  `medicine_stock_movements`, stok minimum, atau kedaluwarsa. Konsekuensi yang
  diakui: apoteker bisa menyerahkan obat yang sebenarnya habis tanpa peringatan.
- `visits.status = 'pembayaran'`; cetak etiket (halaman cetak sederhana).

*Titik demo:* resep diserahkan lewat scan QR → status resep `selesai`, etiket tercetak.

## FASE 6 — Pembayaran & kwitansi (admin) — **belum ada pemilik**

**Prasyarat:** FASE 5.

**Isi teknis:**
- **Pembayaran ditangani admin, bukan kasir/petugas** (sementara): daftar kunjungan
  berstatus `pembayaran` → rincian `visit_services` (jasa) + `prescription_items` (obat)
  → `payments`: `nomor_kwitansi` unik, `total_jasa`, `total_obat`, `total_bayar`,
  `dibayar_at`.
- **Tanpa payment gateway**: admin men-toggle `payments.status`
  (`belum_lunas`/`lunas`) dan `payments.metode` (`transfer`/`cash`); setiap perubahan
  dicatat di `activity_logs` (siapa, kapan, dari apa ke apa). Kunjungan jadi `selesai`
  saat `lunas`.
- Kwitansi dicetak dari `payments.nomor_kwitansi`.

*Titik demo:* admin menandai lunas → kwitansi bernomor tercetak, kunjungan `selesai`.

## FASE 7 — Admin: data master + statistik — **belum ada pemilik**

**Prasyarat:** tidak ketat (bisa jalan kapan saja; lebih berguna setelah FASE 4 ada data).

**Isi teknis:** resource Filament untuk poli, dokter, jadwal, layanan, ICD-10
(menggantikan ketergantungan pada seeder) — **obat tidak perlu**, resep berupa teks
bebas; dashboard statistik: kunjungan per periode + penyakit terbanyak (dari
`medical_record_diagnoses`); laporan + ekspor CSV (PDF/Excel opsional, tergantung
keputusan scope). **Belum masuk:** statistik pendapatan dan kartu stok menipis —
pendapatan belum dibuat, stok ditunda (`DECISIONS.md`).

*Titik demo:* dosen melihat jadwal dokter ditambah lewat panel, lalu statistiknya berubah.

## FASE 8 — Chatbot AI — **Jian**

**Prasyarat:** FASE 2 (booking) dan idealnya FASE 4 (triase terlihat dokter).

**Isi teknis:**
- Basis pengetahuan `chatbot_knowledge` (CRUD di panel admin) + riwayat di
  `chat_conversations` & `chat_messages`; **pengaturan suara** diatur admin dari panel
  yang sama.
- **Tool calling memakai `BookingService` yang sama** dengan FASE 2: cek jadwal, buat
  janji, cek janji, batal janji. Model **wajib minta konfirmasi** sebelum membuat janji —
  perilaku ini diuji, bukan diasumsikan (satu model terbukti langsung booking sendiri).
- Triase → `triage_results` (`ringkasan_keluhan`, `poli_disarankan`, `tingkat_urgensi`),
  terlihat dokter sebelum pasien masuk; **disclaimer "bukan pengganti dokter"** wajib tampil.
- Keamanan: kunci API hanya di server, data demo sintetis, batas panjang/kecepatan jawaban.

*Titik demo:* "saya demam 3 hari" → saran poli + tawaran jam → pasien menyetujui → janji
masuk database (ditunjukkan di layar petugas).

## FASE 9 — AI Speech — **Jian**

**Prasyarat:** FASE 3 (antrean), FASE 4 (SOAP), FASE 8 (chatbot).

**Isi teknis (urutan aman: jalur bebas kuota dulu):**
- 9a **TTS panggilan antrean** (petugas) — pakai nomor antrean + nama; catat `speech_logs`
  (`konteks='antrean'`, `tipe='tts'`).
- 9b **STT pasien → chatbot** (`konteks='chatbot'`).
- 9c **Dikte SOAP** dokter → isi `medical_records` + `diinput_via_suara = true` +
  `speech_logs.medical_record_id` (`konteks='soap'`). Groq Whisper sebagai jalur dikte.
- 9d Opsional: **Gemini Live native audio** (token dibuat server, kunci tidak sampai ke
  browser) sebagai mode tambahan yang gagal dengan sopan kalau kuota habis.

*Titik demo:* dokter mendikte → teks masuk SOAP; petugas memanggil antrean → terdengar.

## FASE 10 — Finalisasi & demo — **semua**

Poles design, seeder data demo yang meyakinkan (pasien + kunjungan + resep contoh), semua
dokumen diperbarui, latihan demo ±10 menit, rencana cadangan kalau internet/AI bermasalah
(data demo lokal + jalur bebas kuota). Tag `v1.0-chatbot-speech`.

---

## Perkiraan beban (kasar, hari kerja efektif)

| Fase | Perkiraan | Catatan |
|---|---|---|
| 1 multi profil + design | 5–7 + 5–7 | dua orang paralel (Ridwan & Juli) |
| 2 janji temu/booking | 5–7 | jalur kritis |
| 3 antrean & check-in | 4–6 | jalur kritis |
| 4 pemeriksaan dokter | 6–8 | paling banyak kolom diisi |
| 5 farmasi (serah obat) | 3–5 | tanpa stok obat |
| 6 pembayaran & kwitansi (admin) | 2–3 | toggle status + cetak kwitansi |
| 7 admin & statistik | 4–6 | bisa lebih lama kalau laporan PDF/Excel masuk |
| 8 chatbot AI | 6–9 | uji tool calling makan waktu |
| 9 AI speech | 5–8 | jalur bebas kuota dulu |
| 10 finalisasi | 3–4 | |

Total ±45–65 hari-orang. Dengan 5 orang bekerja paralel (setelah FASE 1 lewat), realistis
**±5–7 minggu** — angka ini harus diadu dengan deadline dari dosen.

## Risiko dan penangkalnya

- **Modul FASE 3–7 belum ada pemilik** → hambatan terbesar. Selesaikan pembagian tugas
  sebelum FASE 1 selesai, kalau tidak fase-fase itu tidak jalan.
- **FASE 1 (multi profil) menyentuh kode bersama** → ✅ sudah lewat: dikerjakan lebih dulu sebelum
  orang lain membangun di atas asumsi "1 akun = 1 pasien", jadi FASE 2 boleh mulai sekarang.
- **Design Juli menyentuh semua halaman** → tetapkan token di `app.css` lebih dulu, lalu
  halaman per halaman; jangan ada redesign besar di akhir.
- **Kuota AI** (FASE 8–9) → jalur bebas kuota dibangun lebih dulu, Gemini Live sebagai
  tambahan; demo wajib punya data sintetis.
- **Scope bisa membengkak** → kandidat potong kalau waktu mepet: laporan PDF/Excel,
  farmasi (serah obat), pembayaran (bisa jadi sekadar toggle status tanpa kwitansi cetak).
