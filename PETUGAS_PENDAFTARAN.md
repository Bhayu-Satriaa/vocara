# Panduan Pengerjaan: Modul Petugas Pendaftaran / Kasir

Proyek: **Klinik Sehat – Sistem Informasi Klinik Kesehatan** (Laravel)
Role yang dikerjakan: **Petugas Pendaftaran / Kasir**

> File ini adalah konteks untuk AI agent (Antigravity). Baca seluruh isi file sebelum menulis kode.
> Kerjakan **satu tugas per sesi**, sesuai urutan di bagian "Urutan Pengerjaan".

---

## 1. Konteks

Dashboard petugas saat ini baru berupa kerangka: daftar tugas statis, tombol "Scan QR pasien", dan tombol "Cari pasien" yang belum berfungsi. Tugas kita adalah mengubah 5 poin tugas menjadi modul yang berfungsi.

Alur bisnis:

```
Pasien datang -> Scan QR / daftar walk-in -> Verifikasi KTP -> Masuk antrean -> Pembayaran & kwitansi
```

Catatan dari UI: halaman antrean dan scan QR dikerjakan **setelah modul janji temu** (dikerjakan anggota kelompok lain). Jangan menimpa atau mengubah modul janji temu; hanya membaca/menyambung ke tabelnya.

---

## 2. Aturan Wajib (Baca Dulu)

1. **Periksa proyek yang sudah ada sebelum menulis apa pun.** Cek `composer.json`, `package.json`, `routes/web.php`, `app/Models`, `database/migrations`, dan struktur `resources/`. Ikuti stack dan konvensi yang sudah dipakai (misalnya Blade, atau Inertia + React/Vue, serta komponen UI yang ada). Jangan memperkenalkan library atau pola baru tanpa alasan.
2. **Jangan duplikasi.** Jika tabel/model (misalnya `pasien`, `janji_temu`, `users`) sudah ada, **pakai yang ada**. Hanya buat migration baru untuk kolom/tabel yang belum ada. Jangan mengubah migration lama yang sudah dijalankan; buat migration baru.
3. **Jangan menyentuh modul role lain** (dokter, admin, pasien) kecuali memang diperlukan, dan jelaskan alasannya.
4. **Akses hanya untuk role petugas pendaftaran.** Semua route modul ini dilindungi middleware auth + pengecekan role (ikuti cara role yang sudah ada di proyek).
5. **Gunakan Bahasa Indonesia** untuk semua teks UI, pesan validasi, dan flash message.
6. **Kode yang bersih:** Form Request untuk validasi, logika bisnis di Service/Action class (bukan menumpuk di controller), Eloquent relationship yang jelas, route diberi nama.
7. **Keamanan:** validasi semua input, gunakan CSRF, jangan tampilkan data sensitif (NIK) lebih dari yang perlu, dan gunakan database transaction untuk operasi multi-tabel.
8. **Tampilan konsisten** dengan dashboard yang ada (sidebar, kartu, warna biru, tipografi). Gunakan komponen UI yang sudah ada.
9. **Jangan menjalankan perintah destruktif** (`migrate:fresh`, `db:wipe`, hapus data) tanpa konfirmasi saya.
10. Setelah selesai tiap tugas, **jelaskan file apa yang dibuat/diubah** dan cara mengujinya.

---

## 3. Urutan Pengerjaan

| No | Tugas | Alasan urutan |
|----|-------|---------------|
| 1 | Pendaftaran pasien walk-in + pembuatan QR | Data pasien adalah fondasi |
| 2 | Scan QR / check-in pasien | Butuh pasien yang punya QR |
| 3 | Verifikasi identitas (KTP) | Dilakukan saat/after check-in |
| 4 | Manajemen antrean & konfirmasi booking online | Butuh kunjungan hasil check-in dan modul janji temu |
| 5 | Pembayaran & kwitansi | Butuh kunjungan yang selesai dilayani |

Setelah tugas 1 sampai 5, lakukan **tugas 6: update dashboard** (lihat bagian 5).

---

## 4. Spesifikasi Per Tugas

### Tugas 1: Pendaftaran Pasien Walk-in

**Tujuan:** Petugas mendaftarkan pasien baru yang datang langsung. Sistem membuat QR unik otomatis yang bisa dicetak atau dikirim.

**Fitur:**
- Form data pasien: nama lengkap, NIK (16 digit, unik), tanggal lahir, jenis kelamin, no. HP, alamat, (opsional) email.
- Saat disimpan, generate **token QR unik** (UUID atau string acak panjang, bukan NIK mentah) dan simpan di data pasien.
- Halaman detail/sukses menampilkan QR (gambar), tombol **Cetak** (halaman print rapi berukuran kartu), dan tombol **Kirim** (minimal: tautan WhatsApp `wa.me` berisi tautan kartu pasien; email jika sudah ada konfigurasi mail).
- Cegah duplikasi: jika NIK sudah terdaftar, tampilkan pesan dan arahkan ke pasien yang sudah ada.

**Teknis:**
- Library QR yang disarankan: `simplesoftwareio/simple-qrcode` (cek dulu apakah sudah terpasang).
- Route contoh: `GET /petugas/pasien/baru`, `POST /petugas/pasien`, `GET /petugas/pasien/{pasien}`, `GET /petugas/pasien/{pasien}/kartu` (versi cetak).
- Menu sidebar baru: **Pasien Baru**.

**Kriteria selesai:**
- [ ] Pasien tersimpan dengan token QR unik
- [ ] NIK duplikat ditolak dengan pesan jelas
- [ ] QR tampil, bisa dicetak, dan ada tombol kirim
- [ ] Validasi tampil dalam Bahasa Indonesia

---

### Tugas 2: Scan QR / Check-in Pasien

**Tujuan:** Petugas memasukkan token QR (tempel hasil scan HP), sistem mencari pasien dan melakukan check-in ke antrean. Pemindai kamera belum ada, jadi **input token manual dulu**.

**Fitur:**
- Halaman **Scan QR pasien** dengan input token + tombol **Cari pasien** (sesuai kartu di dashboard).
- Jika token valid: tampilkan ringkasan pasien (nama, usia, NIK tersamar) dan tombol **Check-in**.
- Jika pasien punya janji temu/booking hari ini, tautkan check-in ke janji temu tersebut. Jika tidak, buat kunjungan walk-in.
- Check-in membuat record **kunjungan** dan nomor antrean hari itu.
- **Setiap pemindaian dicatat** ke `log_scan` (siapa petugasnya, token, hasil: berhasil/tidak ditemukan, waktu).
- Cegah check-in ganda untuk pasien yang sama di hari yang sama (beri peringatan, jangan crash).

**Teknis:**
- Route contoh: `GET /petugas/scan`, `POST /petugas/scan/cari`, `POST /petugas/kunjungan` (check-in).
- Buat struktur agar nanti mudah ditambah pemindai kamera (misalnya library JS `html5-qrcode`) tanpa mengubah backend.

**Kriteria selesai:**
- [ ] Token valid menemukan pasien; token salah menampilkan pesan
- [ ] Check-in menghasilkan kunjungan + nomor antrean
- [ ] Semua pemindaian tercatat di log
- [ ] Check-in ganda di hari yang sama dicegah

---

### Tugas 3: Verifikasi Identitas (KTP)

**Tujuan:** Petugas mencocokkan data pasien di sistem dengan KTP fisik.

**Fitur:**
- Pada halaman check-in / detail kunjungan, tampilkan data pasien (NIK, nama, tgl lahir) berdampingan dengan checklist pencocokan.
- Aksi: **Terverifikasi** atau **Tidak cocok** (catatan wajib diisi jika tidak cocok).
- Simpan status verifikasi, petugas yang memverifikasi, waktu, dan catatan.
- Jika tidak cocok, petugas bisa membuka form koreksi data pasien.
- Tampilkan badge status verifikasi di daftar antrean.

**Teknis:**
- Tambah kolom di `kunjungan` (atau tabel `verifikasi_identitas` jika lebih rapi): `status_verifikasi`, `diverifikasi_oleh`, `diverifikasi_pada`, `catatan_verifikasi`.
- **Jangan menyimpan foto KTP** kecuali diminta secara eksplisit.

**Kriteria selesai:**
- [ ] Status verifikasi tersimpan lengkap dengan siapa dan kapan
- [ ] Catatan wajib saat "Tidak cocok"
- [ ] Badge verifikasi tampil di antrean

---

### Tugas 4: Antrean & Konfirmasi Booking Online

**Tujuan:** Mengelola antrean hari ini dan memproses booking online.

**Fitur antrean:**
- Daftar antrean hari ini: nomor, nama pasien, poli/dokter (jika ada), jam check-in, status.
- Status: `menunggu` -> `dipanggil` -> `dilayani` -> `selesai` / `batal`.
- Aksi per baris: **Panggil**, **Batalkan**, ubah status (validasi urutan status yang masuk akal).
- Filter status dan pencarian nama; segarkan otomatis berkala (polling sederhana sudah cukup).

**Fitur booking online:**
- Daftar booking berstatus `menunggu konfirmasi`.
- Aksi: **Konfirmasi** atau **Tolak** (alasan wajib saat menolak).

**Teknis:**
- Booking/janji temu berasal dari **modul anggota lain**. Baca struktur tabelnya, **jangan diubah**; tambahkan hanya relasi atau kolom status jika benar-benar perlu, dengan migration baru dan catatan di laporan.
- Jika modul janji temu belum selesai, buat bagian booking dengan **data dummy/seeder** dan beri komentar `TODO: sambungkan ke modul janji temu`.

**Kriteria selesai:**
- [ ] Antrean tampil dan statusnya bisa diubah dengan benar
- [ ] Booking bisa dikonfirmasi/ditolak
- [ ] Transisi status tidak bisa melompat sembarangan

---

### Tugas 5: Pembayaran & Kwitansi

**Tujuan:** Mencatat pembayaran kunjungan dan mencetak kwitansi.

**Fitur:**
- Pilih kunjungan (yang sudah dilayani/selesai) -> input rincian tagihan (item, jumlah, harga) atau total.
- Metode bayar: tunai, transfer, QRIS/e-wallet, (BPJS/asuransi bila relevan).
- Untuk tunai: input uang diterima dan hitung kembalian otomatis.
- Simpan pembayaran dengan **nomor kwitansi unik** (contoh format: `KWT-20250101-0001`).
- **Kwitansi** dalam halaman print atau PDF: identitas klinik, nomor, tanggal, nama pasien, rincian, total, metode bayar, nama kasir.
- Daftar riwayat pembayaran dengan pencarian dan filter tanggal.
- Pembayaran yang sudah tersimpan **tidak bisa diedit sembarangan**; gunakan status `batal` dengan alasan.

**Teknis:**
- PDF: `barryvdh/laravel-dompdf` (cek dulu apakah sudah ada) atau cukup halaman print CSS.
- Simpan nominal sebagai integer (rupiah) atau decimal, **bukan float**.
- Gunakan database transaction saat menyimpan pembayaran + rincian.

**Kriteria selesai:**
- [ ] Pembayaran tersimpan dengan nomor kwitansi unik
- [ ] Kembalian terhitung benar
- [ ] Kwitansi bisa dicetak/diunduh
- [ ] Pembayaran tidak bisa dihapus, hanya dibatalkan dengan alasan

---

## 5. Tugas 6: Update Dashboard

Setelah modul jadi, ubah dashboard petugas dari daftar tugas statis menjadi ringkasan nyata:
- Kartu statistik: jumlah antrean hari ini, booking menunggu konfirmasi, pasien sudah check-in, total pembayaran hari ini.
- Pintasan cepat ke Scan QR, Pasien Baru, Antrean, Pembayaran.
- Hilangkan teks "Belum ada modul di sini".
- Perbarui menu sidebar sesuai halaman baru.

---

## 6. Rancangan Database (Acuan)

> **Hanya acuan.** Cocokkan dengan tabel yang sudah ada di proyek. Buat hanya yang belum ada.

| Tabel | Kolom utama |
|-------|-------------|
| `pasien` | id, nama, nik (unique), tanggal_lahir, jenis_kelamin, no_hp, alamat, email, qr_token (unique) |
| `kunjungan` | id, pasien_id, janji_temu_id (nullable), tanggal, nomor_antrean, status, waktu_checkin, status_verifikasi, diverifikasi_oleh, diverifikasi_pada, catatan_verifikasi |
| `log_scan` | id, user_id, token, hasil, pasien_id (nullable), created_at |
| `pembayaran` | id, kunjungan_id, nomor_kwitansi (unique), total, metode, uang_diterima, kembalian, status, dibuat_oleh, catatan_batal |
| `pembayaran_item` | id, pembayaran_id, deskripsi, jumlah, harga |

Relasi: `Pasien hasMany Kunjungan`, `Kunjungan belongsTo Pasien`, `Kunjungan hasOne Pembayaran`, `Pembayaran hasMany PembayaranItem`.

---

## 7. Struktur Kode yang Diharapkan

```
app/
  Http/
    Controllers/Petugas/   (PasienController, ScanController, KunjunganController, AntreanController, PembayaranController)
    Requests/Petugas/      (Form Request per aksi)
  Models/
  Services/ atau Actions/  (logika check-in, pembuatan nomor antrean, pembuatan kwitansi)
database/
  migrations/
  seeders/
routes/web.php             (grup route /petugas dengan middleware auth + role)
```

Sesuaikan nama folder dengan konvensi yang sudah ada di proyek jika berbeda.

---

## 8. Definisi Selesai (Berlaku untuk Semua Tugas)

- [ ] Migration berjalan tanpa error di database bersih
- [ ] Route terlindungi auth + role petugas
- [ ] Validasi lengkap dengan pesan Bahasa Indonesia
- [ ] Tampilan konsisten dengan dashboard yang ada dan responsif
- [ ] Tidak ada error di log saat alur utama diuji
- [ ] Alur utama diuji manual (tulis langkah ujinya), idealnya ditambah feature test Laravel
- [ ] Ringkasan perubahan: daftar file baru/diubah + cara menguji

---

## 9. Template Prompt per Sesi

Salin ke Antigravity, ganti nomor tugasnya:

```
Baca PETUGAS_PENDAFTARAN.md sampai selesai.
Sekarang kerjakan TUGAS [nomor] saja.
Sebelum menulis kode, periksa struktur proyek yang ada dan beri saya rencana singkat
(file yang akan dibuat/diubah). Tunggu persetujuan saya, lalu implementasikan.
Setelah selesai, berikan ringkasan perubahan dan langkah pengujian manual.
```