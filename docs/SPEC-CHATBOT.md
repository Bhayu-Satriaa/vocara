# Spec: Chatbot Dasar (basis pengetahuan + FAQ, tanpa suara)

Rancangan fitur chatbot bagian Jian, dikerjakan di branch `jian/chatbot-dasar`.
**Tahap 1 saja** (tanpa panggilan AI, tanpa suara) supaya bisa didemokan tanpa kunci API
dan tanpa kuota. Tahap 2 (jawaban oleh model) dan Tahap 3 (suara/triase) ditulis terpisah.

## 1. Tujuan dan ruang lingkup

**Termasuk (Tahap 1):**

- Admin mengelola **basis pengetahuan** chatbot (kategori, pertanyaan, jawaban, aktif/tidak).
- Pasien punya halaman **chat teks** dengan bot, riwayat percakapan tersimpan.
- Bot menjawab dari basis pengetahuan memakai **pencocokan kata kunci** (tanpa AI).
- Pertanyaan yang tidak dikenali dijawab dengan pesan baku + arahan ke petugas.
- Disclaimer "bukan pengganti dokter".

**Tidak termasuk (menyusul):**

- Jawaban yang dirumuskan model AI (Tahap 2, lewat 9Router).
- Tool "buat janji temu" — **menunggu `BookingService`** (FASE 2, wilayah Nabil).
- Suara: pembacaan jawaban (TTS) dan bicara ke chatbot (STT) — Tahap 3.
- Triase otomatis + ringkasan untuk dokter (mengisi `triage_results`) — Tahap 3.

## 2. Data yang dipakai (tanpa migrasi baru)

Semua tabel sudah ada di skema sejak awal — **Tahap 1 tidak menambah migrasi apa pun**:

| Tabel | Kolom yang dipakai | Catatan |
|---|---|---|
| `chatbot_knowledge` | `kategori`, `pertanyaan`, `jawaban`, `is_active`, `updated_by` | sumber jawaban FAQ |
| `chat_conversations` | `user_id`, `patient_id`, `mode` (`teks`/`suara`), `started_at`, `ended_at` | `mode='teks'` untuk Tahap 1; `patient_id` diisi profil aktif |
| `chat_messages` | `conversation_id`, `pengirim` (`pengguna`/`bot`), `isi`, `tipe_input`, `intent`, `dibacakan_tts` | `tipe_input='teks'`, `dibacakan_tts=false` di Tahap 1 |
| `triage_results` | — | **belum dipakai**, disiapkan untuk Tahap 3 |

Model `ChatbotKnowledge`, `ChatConversation`, `ChatMessage`, `TriageResult` sudah ada
beserta relasinya (`messages()`, `triageResult()`), jadi tidak perlu model baru.

## 3. Peran dan hak akses

- **Admin** — membuat/mengubah/menonaktifkan basis pengetahuan; melihat daftar percakapan.
  `chatbot_knowledge.updated_by` diisi otomatis dari admin yang menyimpan.
- **Pasien** — hanya percakapan milik sendiri; hanya profil yang sedang dipakai
  (`profilAktif()`) yang dicatat di `chat_conversations.patient_id`.
- **Petugas / dokter** — tidak diberi akses di Tahap 1 (lihat bagian 9, butuh keputusan tim).

## 4. Alur percakapan

1. Pasien membuka `/chat`. Sistem mencari percakapan aktif (belum `ended_at`, `mode='teks'`
   milik user itu); kalau tidak ada, dibuat baru dengan `started_at = now()`.
   Ada tombol **"Percakapan baru"** yang mengisi `ended_at` percakapan lama lalu membuat baru.
2. Pasien menulis pertanyaan (maks **500 karakter**) → disimpan sebagai `chat_messages`
   dengan `pengirim='pengguna'`, `tipe_input='teks'`.
3. **Pencocokan jawaban** (`ChatbotService::cariJawaban`): teks dinormalkan (huruf kecil,
   tanda baca dibuang, spasi dirapikan) lalu dibandingkan dengan `pertanyaan` dan `kategori`
   pada `chatbot_knowledge` yang `is_active = true` memakai **skor irisan kata kunci**.
4. **Skor ≥ ambang** → jawaban disimpan sebagai `pengirim='bot'`, `intent='faq'`.
   **Skor < ambang** → pesan baku: *"Maaf, saya belum punya jawaban untuk itu. Silakan
   hubungi petugas, atau sampaikan ke dokter saat kunjungan."* + `intent='tidak_dikenal'`.
5. Setiap jawaban bot menampilkan **disclaimer**: *"Informasi ini bukan pengganti
   pemeriksaan dokter."* (wajib untuk kategori yang menyangkut gejala/penyakit).

## 5. Kontrak teknis

- **Route pasien** (di `routes/pasien.php`, di dalam grup `auth` + `role:pasien`):
  - `GET  /chat` → halaman chat (`pasien.chat`)
  - `POST /chat/kirim` → kirim pesan (`pasien.chat.kirim`), butuh profil lengkap
    (middleware `profil`) supaya `patient_id` selalu terisi.
- **Controller** `App\Http\Controllers\Pasien\ChatController` — `index()`, `kirim()`.
- **Service** `App\Services\ChatbotService` — logika pencocokan **satu tempat**, supaya
  bisa diuji tanpa HTTP dan nanti dipakai jalur suara (Tahap 3) juga.
- **Admin** — resource Filament `ChatbotKnowledgeResource` (pola sama seperti
  `UserResource` yang sudah ada: `canAccess()` khusus role `admin`), plus seeder
  `ChatbotKnowledgeSeeder` berisi ±8 FAQ contoh (jam buka, syarat daftar, bawa KTP,
  cara pakai QR, alur pendaftaran, biaya, dsb).
- Setelah mengubah Blade: `php artisan view:clear`.

## 6. Keamanan dan etika

- **Tahap 1 tidak memanggil AI sama sekali** — tidak ada kunci API, aman untuk demo.
- Tahap 2 (nanti): kunci model hanya di `.env` server, sistem prompt disimpan di server,
  demo memakai data sintetis, dan tiap pemakaian dicatat di `activity_logs`.
- Isi chat **bukan rekam medis** — jangan menyalin keluhan pasien ke `medical_records`.
- Riwayat percakapan hanya bisa dibuka pemiliknya; admin hanya melihat daftar percakapan
  (bukan mengubah isinya).
- Bot tidak memberi diagnosis atau dosis obat; untuk pertanyaan seperti itu jawabannya
  mengarahkan ke dokter (dikunci di system prompt / pesan baku).

## 7. Definisi selesai dan daftar test

Definisi selesai: PR di-approve & merge, test lama tidak menurun, dan halaman chat bisa
dipakai dengan basis pengetahuan hasil seeder.

Test yang harus ada:

1. Admin bisa membuat & menonaktifkan pengetahuan dari panel; `updated_by` terisi.
2. Pasien yang membuka CRUD pengetahuan ditolak (403).
3. Pertanyaan yang ada di basis pengetahuan → jawaban diambil dari basis (bukan AI) dan
   tersimpan dengan `pengirim='bot'`, `intent='faq'`.
4. Pengetahuan dengan `is_active = false` tidak dipakai menjawab.
5. Pertanyaan tidak dikenali → pesan baku + `intent='tidak_dikenal'`.
6. Percakapan dan pesan tersimpan dengan `patient_id` = profil aktif milik user itu.
7. Pasien hanya bisa membuka riwayat percakapannya sendiri, bukan milik pasien lain.
8. Pertanyaan kosong atau lebih dari 500 karakter ditolak dengan pesan kesalahan.
9. Halaman chat butuh profil lengkap (belum lengkap → diarahkan ke `/profil`).

## 8. Urutan pengerjaan (PR kecil, satu fitur tuntas)

1. Seeder basis pengetahuan + resource Filament untuk admin (CRUD + aktif/nonaktif).
2. Halaman chat pasien + penyimpanan percakapan/pesan (belum ada pencocokan).
3. `ChatbotService` pencocokan + pesan baku + disclaimer.
4. Test 1–9 di atas, lalu perbarui `docs/FEATURES.md` (bagian chatbot) dan
   `docs/DECISIONS.md` kalau ada keputusan baru.

## 9. Masih terbuka (perlu keputusan tim)

1. **Ambang skor** kemiripan dan siapa yang mengisi basis pengetahuan awal (Jian/admin?)
   — dulu pernah dibahas bahwa admin mengelola basis pengetahuan.
2. **Retensi riwayat** percakapan: disimpan berapa lama? (`chat_conversations` bisa tumbuh).
3. **Bolehkah petugas melihat percakapan pasien?** Kalau ya, perlu dasar kewenangan dan
   pencatatan audit di `activity_logs`.
4. **Kapan Tahap 2 (AI) dimulai** dan siapa yang mengisi `LLM_*` di `.env` (nilai gateway
   9Router) — serta keputusan tetap: demo memakai data sintetis.
5. Bahasa jawaban: Indonesia baku, singkat (maks ±3 kalimat), tanpa istilah medis rumit.
