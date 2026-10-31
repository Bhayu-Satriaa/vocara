# AGENTS.md — Sistem Klinik Kesehatan

Konteks utama repo ini. Agent/AI mana pun yang bekerja di sini membaca dokumen ini
lebih dulu. Bahasa komunikasi dengan pengguna: **Bahasa Indonesia**.

## Project

Sistem informasi **klinik kesehatan berbasis web** untuk tugas Project Based
Learning (PBL) perkuliahan. Cakupannya alur pasien dari registrasi sampai
pembayaran, ditambah **chatbot AI** dan **AI Speech** sebagai fitur wajib, serta
**QR code pasien** untuk mempercepat registrasi dan check-in.

Dokumen pendamping: `README.md` (pengantar + cara menjalankan),
`docs/FEATURES.md` (fitur per role dan alur), `docs/DATABASE.md` (struktur data),
`docs/DECISIONS.md` (keputusan dan hal yang masih terbuka), `klinik_schema.sql`
(skema database lengkap), `docs/boost-guidelines.md` (panduan setup Laravel Boost
bawaan skeleton — referensi tooling, **bukan** aturan proyek).

## Stack

- Laravel (PHP) + Blade + **Tailwind CSS**
- **MySQL** (InnoDB, utf8mb4) — skema lengkap ada di `klinik_schema.sql`

## Keputusan final (jangan diusulkan ulang)

- Platform: **web**.
- Database: **MySQL** (InnoDB, utf8mb4).
- Login **pasien**: **Google OAuth** (Laravel Socialite) + **nomor WA sebagai data
  profil wajib**, tanpa OTP dulu. Middleware memblokir fitur booking dan QR sampai
  profil lengkap (halaman Lengkapi Profil).
- Login **staf**: **email + password**, akun dibuat oleh admin. Staf tidak
  mendaftar sendiri dan tidak memakai Google.
- **QR pasien dibuat otomatis saat pasien terdaftar.** Isi QR hanya **token acak
  (UUID)** — bukan data identitas, bukan data medis.
- **Multi-profile pasien** (disetujui 27 Okt 2026): satu akun boleh memegang beberapa
  data diri (diri sendiri, anak, pasangan). `patients.is_utama` menandai data diri yang
  dipakai untuk kartu QR & booking, `patients.hubungan_keluarga` menjelaskan pemiliknya.
  `patients.user_id` **tidak** UNIQUE lagi; pembeda antar data diri hanya
  **`patients.no_rm`** (unik, dibuat otomatis).
- **NIK tidak disimpan** di sistem (dihapus 27 Okt 2026). Jangan menambahkan kolom,
  field form, atau validasi NIK baru.
- **Surat sakit dikesampingkan** dari scope; jangan ditambahkan kecuali diminta ulang.
- Fitur wajib yang sudah disepakati tim: **chatbot AI** dan **AI Speech**
  (speech-to-text dan/atau text-to-speech).

## Aturan keamanan dan batasan

- **Chatbot bukan pengganti dokter.** Selalu tampilkan disclaimer pada triase dan
  saran; keputusan medis tetap di tangan dokter.
- Rekam medis adalah data sensitif: kontrol akses per role, dan catat aksesnya di
  `activity_logs`.
- Login Google **hanya** untuk role `pasien`. Jangan membuka pendaftaran staf lewat
  Google.
- Nomor WA belum diverifikasi OTP; verifikasi identitas sebenarnya dilakukan petugas
  dengan mencocokkan **nama, tanggal lahir, dan alamat** dengan KTP.
- Hasil scan QR hanya boleh diproses pengguna yang sudah login dengan role
  berwenang; setiap scan dicatat di `qr_scan_logs`.
- Jangan memasukkan data identitas pribadi atau data medis ke dalam QR.
- Jangan menambahkan fitur surat sakit.
- Fitur yang masih berstatus "diusulkan" di `docs/DECISIONS.md` hanya dikerjakan
  kalau pengguna menyetujuinya.

## Konvensi kode

- Ikuti konvensi Laravel: **migrasi** untuk setiap perubahan skema, **Eloquent**
  untuk akses data, **policy/middleware per role** untuk kontrol akses, serta
  **Blade + Tailwind** untuk tampilan.
- Harga disalin ke baris transaksi saat transaksi terjadi (`visit_services.harga`,
  `prescription_items.harga`) — tidak dihitung ulang dari data master.
- Catatan hasil dikte ditandai `medical_records.diinput_via_suara`.
- Data master (poli, dokter, jadwal, tarif, ICD-10, obat) dikelola admin.

## Perintah penting

Belum diketahui dari dokumen sumber — **jangan menebak**, isi setelah setup proyek
disepakati tim:

- TODO: perintah instalasi dependensi
- TODO: perintah migrasi database
- TODO: perintah menjalankan server lokal
- TODO: perintah test
