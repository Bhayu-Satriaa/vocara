# Catatan Keputusan

Ringkasan keputusan yang sudah diambil, beserta alasannya. Bagian yang belum
diputuskan ada di bawah. **Keputusan di tabel ini jangan diusulkan ulang** kecuali
tim membatalkannya secara eksplisit di sini.

| Konteks | Keputusan | Alasan / catatan |
|---|---|---|
| Bentuk aplikasi | Web | Keputusan tim; fitur wajib (chatbot, speech) akan dipakai dari browser |
| Database | MySQL, InnoDB, utf8mb4; skema lengkap di `klinik_schema.sql` (23 tabel) | Satu sumber kebenaran untuk struktur data |
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
| Dashboard staf | Back office memakai **Filament v5** di `/admin` (keputusan Jian: "biar cepat, nanti kita redesign") | Menghemat waktu; resource Filament dibatasi per role (`canAccess`), akses panel hanya untuk staf aktif (`canAccessPanel`). Halaman admin Blade lama sudah dihapus. Tampilan masih tema bawaan, redesign menyusul |
| Format nomor rekam medis | `RM-` + 6 angka urut (`RM-000001`) | Belum ditetapkan di dokumen sumber; dipilih supaya singkat dibaca di kartu pasien dan mudah diurutkan. **Perlu konfirmasi tim.** |
| Gambar QR pasien | Belum dibuat — sementara halaman kartu QR hanya menampilkan tokennya | Butuh paket pembuat QR (mis. `simplesoftwareio/simple-qrcode`); aturan repo: paket baru harus disetujui seluruh kelompok dulu |
| Slot jam kunjungan | `visits.jam` (TIME, NOT NULL) + `doctor_schedules.durasi_slot` (menit, default 30) | Booking harus punya jam, bukan hanya tanggal. Backend yang menghitung pilihan jam dari `jam_mulai`, `jam_selesai`, dan `durasi_slot` — bukan model AI |
| Anti double-booking | Ditegakkan database: `UNIQUE uq_slot_dokter (doctor_id, tgl_kunjungan, jam, slot_aktif)`; `slot_aktif` kolom generated yang bernilai NULL bila status `batal` | Jadwal bentrok tidak boleh bergantung pada pengecekan `if` di aplikasi. Kunjungan batal tetap menyimpan riwayat, tapi slotnya bebas dipesan ulang |
| **Suara (AI speech)** | **Gemini Live API native audio** — model `models/gemini-2.5-flash-native-audio-preview-12-2025`, suara `Kore` — sebagai jalur utama; **cadangan bebas kuota**: TTS/STT bawaan browser (`speechSynthesis`, `SpeechRecognition`) dan Groq Whisper untuk dikte | Sudah terbukti jalan di prototipe lama (`Politani/Semester 3/PBL - Appointment AI/poc`): speech-to-speech native, bukan STT+TTS terpisah. **Sementara ini memakai free tier** (billing/kredit ditunda — lihat "Masih terbuka" no. 12), jadi jalur cadangan bebas kuota wajib tetap dibangun, bukan opsional |
| **Otak chatbot (teks)** | Model dari **9Router lokal** (endpoint OpenAI-compatible `http://127.0.0.1:20128/v1`); prototipe lama memakai `cmc/moonshotai/Kimi-K2.5` dengan cadangan `cmc/moonshotai/Kimi-K2.7-Code-Highspeed`. Alternatif: **Command Code Provider API** (`https://api.commandcode.ai/provider/v1`) kalau plan-nya bukan "Go" | Hasil uji 12 model × 3 skenario: Kimi-class paling cepat (1,8 / 2,1 / 2,1 detik), Kimi-K2.6 paling lambat (7,0 / 8,8 / 4,4). Command Code **tidak punya model suara** (93 model, semuanya teks) — jadi jalur teks dan suara memang terpisah |
| **Data yang dikirim ke AI** | Demo dan pengembangan memakai **data sintetis**; keluhan pasien nyata tidak dikirim mentah ke penyedia AI | Data pada free tier boleh dipakai penyedia untuk perbaikan produk, sedangkan rekam medis sensitif (aturan di `AGENTS.md`) |

## Masih terbuka

Butuh keputusan tim:

1. ~~**Jenis AI speech** (STT, TTS, atau keduanya)~~ → sudah diputuskan: lihat baris
   "Suara (AI speech)". Tiga pemakaian di `docs/FEATURES.md`: TTS panggilan antrean,
   STT dikte SOAP, STT+TTS chatbot pasien.
2. ~~**Penyedia model AI/API**~~ → sudah diputuskan: lihat baris "Otak chatbot (teks)"
   dan "Suara (AI speech)".
3. **Jumlah anggota kelompok** dan **batas waktu** pengerjaan.
4. **Pembagian tugas** per anggota.
5. **Perintah teknis** (instalasi, migrasi, menjalankan, test) — sengaja belum ditulis
   di `AGENTS.md`/`README.md` sampai setup disepakati.
6. **10 tabel belum punya `created_at`/`updated_at`** (polis, doctors,
   doctor_schedules, diseases, medical_record_diagnoses, services, visit_services,
   medicines, prescription_items, chat_conversations). Sementara ini model
   Eloquent-nya memakai `$timestamps = false`; tambah kolomnya kalau tim mau seragam.
7. **Pasien walk-in tanpa NIK** tidak bisa didaftarkan karena `patients.nik` dan
   `patients.no_wa` NOT NULL. Perlu keputusan: wajibkan NIK, atau sediakan nilai
   sementara.
8. **Nomor antrean** (`visits.no_antrian`) belum jelas kapan dibuat — saat booking
   atau saat check-in. `uq_antrian` baru mengikat setelah nomornya terisi.
9. **PHP untuk Laravel 13.30:** `composer.lock` menuntut PHP **>= 8.4.1**, sedangkan
   Laragon yang terpasang hanya PHP 8.1.10 dan 8.3.12 (artisan gagal dengan
   "Composer detected issues in your platform"). Yang bisa dipakai sekarang:
   `C:\php\php.exe` (PHP 8.5.6, sudah ada di PATH Windows). Pilihan: pasang PHP 8.4
   di Laragon, atau semua perintah artisan dijalankan lewat `C:\php\php.exe`.
10. **Kredensial Google OAuth** (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`,
    `GOOGLE_REDIRECT_URI`) belum diisi, jadi tombol "Masuk dengan Google" masih
    memutar balik ke halaman login dengan pesan yang jelas. Harus dibuatkan OAuth
    Client (tipe Web application) di Google Cloud Console, redirect URI
    `http://localhost:8000/auth/google/callback`.
11. **Gambar QR untuk diunduh/dicetak** belum ada — butuh persetujuan paket
    pembuat QR. Setelah itu baru bisa masuk ke desain kartu pasien.
12. **Billing project `ai-appointment` — ditunda (keputusan sementara Jian).**
    Untuk sekarang proyek memakai **free tier** Gemini; tim akan memutuskan nanti
    apakah perlu mengaktifkan billing/kredit. Konsekuensi yang harus diingat:
    kuota free tier ketat untuk model **preview** seperti Live native audio, dan
    **data free tier boleh dipakai Google untuk perbaikan produk** → demo wajib
    memakai data sintetis. Karena itu jalur suara bebas kuota (TTS/STT bawaan
    browser + Groq Whisper) dibangun sebagai tulang punggung, bukan cadangan
    belaka. Bila billing nanti aktif: kredit bulanan **Google Developer Program**
    (manfaat langganan Google AI Pro) boleh dipakai untuk Gemini API. Catatan
    resmi: manfaat AI Pro untuk developer hanya berlaku di UI AI Studio,
    sedangkan pemakaian API key/aplikasi "billed and managed separately".
13. **Prefix `cmc/` di 9Router** diduga berasal dari langganan **Command Code**
    (katalognya sama: Kimi, DeepSeek V4.1 Flash, Qwen 3.7 Plus, MiniMax M3, GLM-5.2,
    MiMo, Nemotron). Perlu dikonfirmasi Jian; saat diperiksa 9Router sedang tidak
    jalan sehingga belum bisa dibuktikan lewat `/v1/models`.

## Fitur tambahan yang pernah diusulkan (belum diputuskan)

Ringkasan rekam medis otomatis oleh AI, pengingat otomatis (email/WA), grafik tanda
vital, telekonsultasi chat sederhana, penjelasan resep dalam bahasa awam, pencatatan
BPJS/asuransi, audit trail dan consent pasien untuk penggunaan data oleh AI.

**Hanya masukkan kalau pengguna menyetujui.**

## Rencana langkah berikutnya (usulan)

1. ~~Migrasi Laravel dari `klinik_schema.sql`~~ — **selesai** (23 tabel, 37 foreign
   key, sudah dijalankan di `klinik_db`).
2. ~~Kode Socialite + halaman Lengkapi Profil~~ — **selesai** (login Google pasien
   sudah diuji dengan akun sungguhan).
3. **Pembagian tugas kelompok** — belum.
4. Fase berikutnya sesuai `docs/FEATURES.md`: modul petugas (verifikasi KTP + scan QR
   check-in) → janji temu/kunjungan → pemeriksaan dokter → farmasi → kasir → data
   master + statistik admin → terakhir chatbot lalu AI speech.
