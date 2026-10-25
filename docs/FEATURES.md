# Fitur per Role dan Alur Utama

Sumber: dokumen konteks kelompok (versi panjang AGENTS.md). Isi di sini adalah
rincian fitur; aturan umum dan konvensi kode ada di `AGENTS.md`.

## Admin

- Manajemen akun semua role.
- Data master: poli, dokter, jadwal, tarif, ICD-10, obat.
- Dashboard statistik: kunjungan, penyakit terbanyak, pendapatan, stok menipis.
- Laporan dan ekspor PDF/Excel.
- Log aktivitas, termasuk scan QR dan akses rekam medis.
- AI: kelola basis pengetahuan chatbot, lihat riwayat percakapan, atur pengaturan suara.

## Petugas pendaftaran / kasir

- Scan QR pasien untuk check-in: data muncul, kunjungan masuk antrean.
- Verifikasi identitas pasien secara online: cocokkan KTP, ubah status ke terverifikasi.
- Daftarkan pasien walk-in (QR dibuat otomatis, bisa dicetak atau dikirim).
- Pencarian manual pasien sebagai cadangan kalau QR hilang.
- Kelola antrean; konfirmasi booking online; pembayaran dan kwitansi.
- AI: panggilan antrean lewat suara (text-to-speech).

## Dokter

- Daftar pasien hari ini; scan QR untuk membuka rekam medis.
- Riwayat rekam medis; input pemeriksaan format SOAP + tanda vital.
- Diagnosis memakai ICD-10.
- Resep elektronik.
- AI: dikte catatan SOAP (speech-to-text); ringkasan triase chatbot sebelum pasien
  masuk.

## Apoteker

- Daftar resep masuk; status resep: menunggu, disiapkan, selesai.
- Scan QR pasien saat penyerahan obat.
- Manajemen stok obat: masuk, keluar, kedaluwarsa, stok minimum.
- Cetak etiket.
- AI (opsional): peringatan sederhana interaksi/duplikasi obat.

## Pasien

- Login Google, lengkapi profil (nomor WA, NIK, tanggal lahir, dll.).
- QR pribadi dengan status *belum terverifikasi* atau *terverifikasi*; unduh/simpan QR.
- Booking jadwal dokter, lihat antrean, riwayat kunjungan/resep/tagihan, jadwal praktik.
- AI chatbot: FAQ, bantu booking, triase awal dan saran poli.
- AI speech: bicara ke chatbot (STT) dan jawaban dibacakan (TTS).

## Lintas role

- Notifikasi: pengingat jadwal, resep siap.
- Kontrol akses berbasis role.

## Bagian AI

Dua jalur yang sudah diputuskan sebagai fitur wajib:

- **Chatbot** — dipakai pasien (FAQ, bantu booking, triase awal & saran poli); admin
  mengelola basis pengetahuan dan melihat riwayat percakapan; dokter bisa melihat
  ringkasan triase sebelum pasien masuk.
- **Speech** — text-to-speech untuk panggilan antrean (petugas) dan pembacaan jawaban
  chatbot (pasien); speech-to-text untuk dikte catatan SOAP (dokter) dan bicara ke
  chatbot (pasien).
- **Aturan wajib:** chatbot bukan pengganti dokter — disclaimer harus tampil pada
  triase dan saran, keputusan medis tetap di tangan dokter.
- Jenis speech yang disepakati, penyedia model AI/API, dan pengaturan suara belum
  final (lihat "Masih terbuka" di `docs/DECISIONS.md`).

## Bagian QR

- QR dimiliki **pasien**, dibuat **otomatis saat pasien terdaftar** — bukan setelah
  pemeriksaan pertama.
- Isi QR hanya **token acak (UUID)** dari `patients.qr_token`. **Jangan** memasukkan
  NIK atau data medis ke dalam QR.
- QR punya status *belum terverifikasi* → *terverifikasi* setelah petugas
  mencocokkan KTP.
- Hasil scan hanya bisa diproses pengguna yang sudah login dengan role berwenang;
  setiap scan dicatat di `qr_scan_logs`.
- Dipakai di beberapa titik: check-in petugas, buka rekam medis (dokter), serah obat
  (apoteker).
- Pasien walk-in: petugas yang mendaftarkan, `patients.user_id` boleh kosong, QR tetap
  dibuat otomatis.

## Alur login dan registrasi pasien

1. Klik **Masuk dengan Google**; akun dengan role `pasien` dibuat otomatis bila belum ada.
2. Diarahkan ke halaman **Lengkapi Profil** (middleware memblokir fitur booking dan QR
   sampai nomor WA terisi).
3. QR dibuat otomatis dengan status `belum`.
4. Saat pertama datang, petugas scan QR dan mencocokkan KTP, lalu status menjadi
   `terverifikasi`.

## Alur status kunjungan

`booking → menunggu → diperiksa → farmasi → pembayaran → selesai`

atau `batal`. Disimpan di `visits.status`.
