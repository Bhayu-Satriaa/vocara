# Spec: Booking Janji Temu lewat Chatbot

Rencana fitur: pasien yang **sudah punya kartu QR** dan **sudah diverifikasi petugas**
dapat membuat janji temu dengan berbicara/menulis ke chatbot. Dikerjakan **sebelum**
form booking web, supaya chatbot tidak menunggu modul lain.

Bergantung pada `docs/SPEC-CHATBOT.md` (Tahap 1 = basis pengetahuan + FAQ tanpa AI).
Catatan penting: kolom `visits.sumber` sudah punya nilai **`chatbot`** sejak awal, jadi
skema memang menyiapkan jalur ini — booking lewat chatbot akan tersimpan dengan
`sumber='chatbot'` (bukan `online`).

## 0. Aturan gerbang (yang diminta Jian)

Chatbot hanya boleh **membuat janji** kalau dua syarat ini terpenuhi:

1. Pasien punya **kartu QR** → artinya baris `patients` sudah ada (pengajuan profilnya
   disetujui admin) dan `no_wa` terisi. Dicek lewat `User::profilAktif()`.
2. Pasien **sudah diverifikasi petugas** → `patients.verification_status = 'terverifikasi'`.

Kalau salah satu belum terpenuhi, chatbot **tidak memanggil tool booking** dan menjawab
dengan arahan:

- Belum punya data diri → *"Lengkapi data dirimu dulu, lalu tunggu persetujuan admin."*
- Sudah punya data diri, belum verifikasi → *"Kartu QR-mu belum diverifikasi. Tunjukkan
  QR-nya ke petugas saat datang pertama kali — setelah itu saya bisa membuatkan janji."*
  (sertakan tautan `/kartu-qr`)

Chatbot **tetap menjawab FAQ** untuk kedua kondisi di atas; yang ditahan hanya aksi booking.

## 1. Fase pengerjaan

### Fase 1 — Basis pengetahuan + chat teks (tanpa AI)
Sesuai `docs/SPEC-CHATBOT.md` Tahap 1: admin mengelola `chatbot_knowledge`, pasien punya
halaman `/chat`, jawaban FAQ dari pencocokan kata kunci, riwayat di `chat_conversations`
+ `chat_messages`. **Fase ini juga jadi jalur cadangan**: kalau gateway model mati atau
kuota habis, chatbot turun ke mode FAQ dan tetap berguna di depan dosen.

### Fase 2 — Lapisan booking bersama (`BookingService`) — **prasyarat teknis**
Ini yang membuat booking "satu pintu". Tanpa ini, chatbot akan menulis `visits` sendiri
dan lahir **dua jalur booking** → sumber double-booking.

Isi service (satu kelas, dipakai chatbot **dan** form web nanti):

- `slotTersedia(Doctor $dokter, string $tanggal): array` — jam operasional dari
  `doctor_schedules` (`hari`, `jam_mulai`, `jam_selesai`, `durasi_slot` default 30 menit,
  `is_active`) dikurangi jam yang sudah terisi di `visits` (status belum `batal`).
- `dokterPraktikPada(string $tanggal): Collection` — dokter yang jadwalnya cocok dengan
  **nama hari** (dihitung backend, bukan oleh model).
- `buat(Patient $pasien, int $dokterId, string $tanggal, string $jam, ?string $keluhan, string $sumber): Visit`
  — menulis `visits` (`patient_id`, `doctor_id`, `poli_id`, `schedule_id`, `tgl_kunjungan`,
  `jam`, `status='booking'`, `sumber`, `keluhan_awal`). Slot bentrok **ditolak database**
  (`uq_slot_dokter`) → tangkap pelanggaran constraint, jangan cek `if exists` dulu.
- `batal(Visit $visit)` — `status='batal'` (slot bebas lagi karena `slot_aktif` jadi NULL).
- `janjiMilik(Patient $pasien)` — daftar janji aktif untuk "janji saya".

Aturan: semua parameter tanggal/jam **divalidasi di service** (tanggal tidak boleh masa
lalu, jam harus termasuk slot yang tersedia, dokter harus praktik hari itu).

### Fase 3 — Otak chatbot: model + tool calling
- `/chat/kirim` mengirim percakapan ke model dari **9Router lokal** (endpoint
  OpenAI-compatible, kunci di `.env` saja) dengan daftar tool:
  `daftar_dokter`, `cek_jadwal`, `buat_janji`, `cek_janji`, `batal_janji`.
- Server menyiapkan konteks: **tanggal hari ini + nama hari** (dihitung backend), profil
  aktif (nama, nomor RM, status verifikasi), dan hasil tiap tool selalu memuat
  **tanggal + nama hari** dalam bentuk yang mudah dibaca manusia.
- **Konfirmasi wajib**: model harus meringkas (dokter, tanggal, hari, jam, poli) dan
  mendapat "ya" eksplisit sebelum `buat_janji`. Ini **diuji**, bukan diasumsikan —
  pengalaman prototipe lama: satu model langsung membuat janji tanpa bertanya, dan model
  seperti itu tidak dipakai.
- Setelah tool dijalankan, hasilnya dikirim balik ke model sebagai tool-response, lalu
  jawaban akhir disimpan di `chat_messages` (`pengirim='bot'`, `intent` = `faq` /
  `tanya_jadwal` / `buat_janji` / `cek_janji` / `batal_janji`).
- **Panel data di samping chat** menampilkan janji yang sedang diproses/dibuat — bukti
  visual untuk demo bahwa bicara benar-benar menulis ke database.

### Fase 4 — Gerbang verifikasi & hari demo
- Gate bagian 0 dipasang di **service/aksi**, bukan hanya di prompt: tool `buat_janji`
  menolak kalau `verification_status != 'terverifikasi'`, walaupun model memanggilnya.
- Data demo memakai **data sintetis** (nama, keluhan, nomor WA palsu).
- Kalau 9Router tidak jalan: chatbot tetap menjawab FAQ, dan tombol/aplikasi memberi tahu
  bahwa pembuatan janji lewat chat belum tersedia.

### Fase 5 (setelah stabil, opsional) — Suara
TTS membacakan jawaban + STT bicara ke chatbot, tercatat di `speech_logs`
(`konteks='chatbot'`). Gemini Live sebagai mode tambahan; jalur bebas kuota lebih dulu.

### Fase 6 (opsional) — Triase untuk dokter
Ringkasan keluhan masuk `triage_results` (`ringkasan_keluhan`, `poli_disarankan`,
`tingkat_urgensi`) dan terlihat dokter sebelum pasien masuk, dengan disclaimer
"bukan pengganti dokter".

## 2. Berkas dan route yang ditambah

- `app/Services/BookingService.php` (Fase 2)
- `app/Services/ChatbotService.php` (Fase 1 pencocokan FAQ + Fase 3 orkestrasi tool)
- `app/Services/AsistenChat.php` (kalau perlu: pemanggilan model 9Router)
- `app/Http/Controllers/Pasien/ChatController.php` — `index`, `kirim`
- `app/Filament/Resources/ChatbotKnowledgeResource` (admin) — Fase 1
- `routes/pasien.php`: `GET /chat`, `POST /chat/kirim`, `GET /janji` (daftar janji pasien)
- `resources/views/pasien/chat.blade.php` (+ panel data janji)
- Seeder `ChatbotKnowledgeSeeder` (FAQ contoh) dan seeder jadwal dokter untuk demo

Tidak ada migrasi baru — semua tabel yang dipakai sudah ada (`chatbot_knowledge`,
`chat_conversations`, `chat_messages`, `visits`, `doctor_schedules`).

## 3. Definisi selesai dan daftar test

Definisi selesai: PR di-approve & merge, `php artisan test` tidak menurun, dan
**demo ulang berhasil**: pasien terverifikasi membuat janji lewat chat → baris muncul di
`visits` dan di layar petugas.

Test minimal:

**BookingService**
1. Dua janji pada dokter + tanggal + jam sama → yang kedua ditolak database (constraint,
   bukan `if`), pesannya ramah.
2. Setelah janji pertama dibatalkan, slot yang sama bisa dipesan lagi.
3. Jam di luar jadwal dokter (atau tanggal lampau) ditolak.
4. Dokter yang tidak praktik pada hari itu tidak muncul di `dokterPraktikPada`.
5. Janji tercatat dengan `sumber='chatbot'` dan `patient_id` = profil aktif.

**Gerbang & chatbot**
6. Pasien belum punya data diri → tool `buat_janji` menolak + chatbot mengarahkan.
7. Pasien punya data diri tapi `verification_status='belum'` → tool menolak, chatbot
   meminta verifikasi QR ke petugas.
8. Pasien terverifikasi → chatbot menjalankan `cek_jadwal` lalu `buat_janji` setelah
   konfirmasi "ya"; jawaban bot & pesan pengguna tersimpan di `chat_messages` dengan
   `intent` yang benar.
9. Percakapan pasien lain tidak bisa dibaca pasien ini (403).
10. FAQ tetap terjawab saat gateway model dimatikan (mode cadangan).

## 4. Urutan PR (kecil, satu fitur tuntas)

1. Fase 1: seeder FAQ + resource admin + halaman chat + pencocokan kata kunci (tanpa AI).
2. Fase 2: `BookingService` + test 1–5 (belum terhubung chatbot).
3. Fase 3a: pemanggilan model + tool `daftar_dokter` & `cek_jadwal` (baca saja).
4. Fase 3b: tool `buat_janji` + konfirmasi wajib + panel data janji.
5. Fase 4: gerbang verifikasi + test 6–7, 10.
6. Tool `cek_janji` & `batal_janji`.
7. Perbarui `docs/FEATURES.md`, `docs/DECISIONS.md`, `docs/DAFTAR-FITUR.md`.

## 5. Risiko dan penangkalnya

- **Dua jalur booking** (chatbot vs form web Nabil) → satu `BookingService`, diumumkan ke
  tim; PR form web Nabil wajib memakai service ini, bukan menulis ulang.
- **Model membuat janji tanpa konfirmasi** → perilaku ini diuji sebelum dipilih; kalau
  gagal, model diganti (katalog 9Router punya banyak pilihan) dan prompt diperjelas.
- **Kuota/gateway mati saat demo** → Fase 1 (tanpa AI) selalu jalan; booking lewat chat
  disebut "belum tersedia" alih-alih error.
- **Salah hari** → nama hari selalu dihitung backend dan dikembalikan di setiap hasil tool.
- **Double booking** → ditegakkan `uq_slot_dokter` di database; pelanggarannya ditangkap
  dan diterjemahkan jadi kalimat ramah.

## 6. Masih terbuka (perlu keputusan tim)

1. **Siapa menulis `BookingService`** — usulan: Jian (karena chatbot jalan lebih dulu),
   lalu Nabil memakai service itu untuk form web. Perlu diumumkan ke tim supaya tidak
   ditulis dua kali.
2. **Rentang tanggal booking** (usulan 14 hari ke depan) dan apakah `doctor_schedules.kuota`
   dipakai sebagai batas jumlah janji per sesi.
3. **Booking untuk anggota keluarga** (multi profil): ikut sekarang (pilih profil di chat)
   atau nanti? Usulan: **nanti**, dengan versi pertama memakai profil aktif saja.
4. **Model 9Router mana yang dipakai** untuk chatbot (kandidat tercepat dari benchmark
   prototipe lama) dan model cadangannya.
5. Apakah janji yang dibuat lewat chatbot boleh dibatalkan pasien sendiri lewat chat
   (tool `batal_janji`) atau hanya lewat halaman `/janji`.
