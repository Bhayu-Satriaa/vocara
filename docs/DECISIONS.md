# Catatan Keputusan

Ringkasan keputusan yang sudah diambil, beserta alasannya. Bagian yang belum
diputuskan ada di bawah. **Keputusan di tabel ini jangan diusulkan ulang** kecuali
tim membatalkannya secara eksplisit di sini.

| Konteks | Keputusan | Alasan / catatan |
|---|---|---|
| Bentuk aplikasi | Web | Keputusan tim; fitur wajib (chatbot, speech) akan dipakai dari browser |
| Database | MySQL, InnoDB, utf8mb4; skema lengkap di `klinik_schema.sql` (26 tabel) | Satu sumber kebenaran untuk struktur data |
| Fitur wajib | Chatbot AI **dan** AI Speech (STT dan/atau TTS) | Disepakati tim sebagai pembeda tugas |
| Tambahan yang diputuskan | QR code pasien | Mempermudah registrasi dan check-in |
| Login pasien | Google OAuth (Laravel Socialite) + **nomor WA wajib** di profil | Tanpa OTP dulu; WhatsApp belum jadi jalur verifikasi. Middleware memblokir booking & QR sampai profil lengkap |
| Data NIK pasien | Disimpan sebagai data profil | Dipakai petugas saat mencocokkan identitas dengan KTP |
| Login staf | Email + password, akun **dibuat admin** | Staf tidak mendaftar sendiri dan tidak memakai Google — memisahkan jalur pasien dari jalur internal |
| Waktu pembuatan QR | **Otomatis saat pasien terdaftar** | Bukan menunggu pemeriksaan pertama; supaya pasien walk-in maupun online langsung punya kartu |
| Isi QR | Token acak (**UUID**) dari `patients.qr_token` | NIK dan data medis tidak boleh masuk QR karena QR mudah difoto/dibagikan. Hasil scan hanya diproses user login berwenang, setiap scan dicatat di `qr_scan_logs` |
| Status QR | `belum` → `terverifikasi` oleh petugas | Nomor WA belum diverifikasi OTP, jadi verifikasi nyata dilakukan petugas dengan mencocokkan KTP |
| Pasien walk-in | Didaftarkan petugas; `patients.user_id` boleh kosong | Tetap dapat QR; pencarian manual disediakan kalau QR hilang |
| Surat sakit | **Dikesampingkan** | Bukan bagian scope saat ini; jangan ditambahkan kecuali diminta ulang |
| Harga layanan & obat | Disalin ke `visit_services.harga` dan `prescription_items.harga` saat transaksi | Menjaga riwayat transaksi tetap benar walau tarif master berubah |
| Input hasil dikte | Ditandai `medical_records.diinput_via_suara` | Jejak asal data untuk audit |
| Peran chatbot | **Bukan pengganti dokter** | Disclaimer wajib pada triase dan saran; keputusan medis tetap di tangan dokter |
| Akses rekam medis | Kontrol akses per role + pencatatan di `activity_logs` | Rekam medis data sensitif |
| Konvensi teknis | Migrasi, Eloquent, policy/middleware per role, Blade + Tailwind | Mengikuti konvensi Laravel |

## Masih terbuka

Butuh keputusan tim:

1. **Jenis AI speech**: speech-to-text, text-to-speech, atau keduanya.
2. **Penyedia model AI/API** untuk chatbot dan speech.
3. **Jumlah anggota kelompok** dan **batas waktu** pengerjaan.
4. **Pembagian tugas** per anggota.
5. **Perintah teknis** (instalasi, migrasi, menjalankan, test) — sengaja belum ditulis
   di `AGENTS.md`/`README.md` sampai setup disepakati.

## Fitur tambahan yang pernah diusulkan (belum diputuskan)

Ringkasan rekam medis otomatis oleh AI, pengingat otomatis (email/WA), grafik tanda
vital, telekonsultasi chat sederhana, penjelasan resep dalam bahasa awam, pencatatan
BPJS/asuransi, audit trail dan consent pasien untuk penggunaan data oleh AI.

**Hanya masukkan kalau pengguna menyetujui.**

## Rencana langkah berikutnya (usulan)

1. Migrasi Laravel dari `klinik_schema.sql`.
2. Kode Socialite + halaman Lengkapi Profil.
3. Pembagian tugas kelompok.
