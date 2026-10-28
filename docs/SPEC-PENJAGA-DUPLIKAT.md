# Spec: Penjaga Duplikat Pasien (tanpa NIK)

Rancangan penjaga agar **satu pasien tidak tertukar dengan pasien lain** dan data diri
yang sama tidak terdaftar berkali-kali — tanpa menyimpan NIK. Status: **rencana
disetujui Jian (27 Okt 2026), dikerjakan di FASE 3.**

## 1. Tujuan dan batas

- Setiap pendaftaran pasien (online lewat Google **maupun** walk-in oleh petugas) dicek
  terhadap data yang sudah ada.
- **Tiap pasien tetap satu baris sendiri.** TIDAK ada penggabungan otomatis, tidak ada
  data pasien yang dipindah atau dihapus oleh sistem. Kalau dua orang mirip, mereka
  tetap dua baris terpisah sampai petugas memutuskan.
- Yang dijaga: kemungkinan **pendaftaran orang yang sama dua kali**.
- Yang TIDAK dijaga: salah ketik nama (mis. "Budi Santosa" vs "Budi Santoso") dan
  perbedaan ejaan; untuk itu ada lapis kedua (pencarian manual, bagian 8).

## 2. Keputusan yang dipakai

| Hal | Keputusan |
|---|---|
| Dasar penjaga | **Sidik jari identitas** = HMAC-SHA256 atas *nama dinormalkan + tanggal lahir* |
| Penegak aturan | **Database** (`UNIQUE`), bukan pengecekan `if` di aplikasi |
| Data pribadi | Tidak menyimpan NIK. Sidik jari tidak bisa dibalik dan tidak pernah ditampilkan |
| Kasus orang berbeda dengan data mirip | Petugas boleh lanjut lewat tombol **"Orang berbeda"** yang menaikkan kolom `salinan` |
| Nomor WA | **Tidak** ikut ke dalam sidik jari (keluarga sering berbagi/ganti nomor) |

## 3. Perubahan skema

Migrasi baru (jangan mengedit migrasi lama):

```
patients.kode_identitas  CHAR(32) NOT NULL          -- hex HMAC-SHA256 (32 karakter pertama)
patients.salinan         TINYINT UNSIGNED NOT NULL DEFAULT 0
UNIQUE (kode_identitas, salinan)
```

- `salinan` = 0 untuk pendaftaran normal; dinaikkan hanya lewat tombol "Orang berbeda".
- Keduanya wajib NOT NULL: nama dan tanggal lahir di `patients` sudah NOT NULL, jadi
  sidik jari selalu bisa dihitung — termasuk untuk pasien walk-in (`user_id` NULL).
- Kunci HMAC diambil dari `.env` sebagai `IDENTITAS_HMAC_KEY` (terpisah dari `APP_KEY`,
  supaya merotasi `APP_KEY` tidak merusak semua sidik jari). Kunci di-`generate` sekali
  dan **tidak pernah** di-commit.

## 4. Normalisasi nama (satu tempat saja)

Di model `Patient`, satu fungsi statis yang dipakai semua jalur:

1. huruf kecil
2. `trim`, lalu spasi/tab berulang dirapikan jadi satu spasi
3. tanda baca (`.`, `,`, `-`, `'`) dibuang
4. gelar tidak dibuang (mis. "dr.") — risiko kecil, dibiarkan dulu agar tidak salah tebak

Contoh:

| Masukan | Hasil normal | Kesimpulan |
|---|---|---|
| `Budi  Santoso` | `budi santoso` | sama |
| `BUDI SANTOSO.` | `budi santoso` | sama |
| `Budi Santosa` | `budi santosa` | **beda** (typo → perlu lapis kedua) |

## 5. Perhitungan sidik jari

```
kode_identitas = substr(hash_hmac('sha256', normalisasi(nama) . '|' . tgl_lahir, IDENTITAS_HMAC_KEY), 0, 32)
```

- Dihitung di hook `creating` **dan** `updating` (kalau nama atau tanggal lahir berubah),
  karena sidik jari basi akan salah menolak pendaftaran yang sah.
- Perubahan nama hanya boleh dilakukan petugas, dan dicatat di `activity_logs`.

## 6. Alur saat data dianggap ganda

1. Petugas menekan Simpan (pendaftaran walk-in) atau pasien melengkapi/menambah data diri.
2. `UNIQUE (kode_identitas, salinan)` menolak → aplikasi menangkap pelanggaran constraint
   (error MySQL `1062`) dan **tidak** menampilkan halaman error.
3. Muncul layar **Data ganda terdeteksi**: menampilkan RM, nama, tanggal lahir, dan status
   data pasien yang sudah ada (data medis TIDAK ditampilkan di layar ini).
4. Dua pilihan:
   - **"Ini orang yang sama"** → dipakai RM yang sudah ada (kunjungan/booking diarahkan ke
     pasien itu). Tidak ada baris baru.
   - **"Orang berbeda"** → baris dibuat dengan `salinan + 1`, dengan alasan wajib dipilih
     (mis. `kembar`, `nama sama`, `lainnya`).
5. Kedua pilihan ditulis ke `activity_logs` dengan aksi `keputusan_duplikat`, memuat RM
   yang dibandingkan dan alasan pilihan petugas.

## 7. Keamanan dan etika

- Sidik jari bukan NIK dan tidak bisa dibalik, tapi **diperlakukan sebagai data internal**:
  tidak ditampilkan di UI, tidak masuk QR/kartu pasien, **tidak masuk log** (log hanya
  menulis RM, bukan sidik jarinya).
- Pencarian pasien tetap butuh login + role, dan pemakaian dicatat (`activity_logs`;
  scan QR di `qr_scan_logs`).
- Layar data ganda sengaja **tidak** menampilkan isi rekam medis, hanya identitas dasar
  yang sudah dipakai petugas untuk mencocokkan KTP (nama, tanggal lahir, nomor RM).

## 8. Lapis kedua: pencarian manual

- Halaman petugas mendapat pencarian pasien: **nama (sebagian) + tanggal lahir**, dan
  sekarang juga **kode pasien/kartu QR**.
- Indeks pendukung: `(tgl_lahir)` dan/atau `(nama_lengkap, tgl_lahir)` — saat ini tabel
  `patients` belum punya indeks untuk pencarian nama (masih full scan).
- Peringatan lunak tetap tampil walau sidik jari lolos (kasus salah ketik), bentuknya
  daftar kandidat, bukan blokir.

## 9. Batas kemampuan (disadari)

| Kasus | Perilaku |
|---|---|
| Nama beda ejaan | lolos sidik jari → tertangkap lapis kedua (warning) |
| Kembar dengan nama & tanggal lahir identik | diblokir → petugas pakai tombol "Orang berbeda" |
| Nama diperbaiki (ejaan) | sidik jari berubah → berpotensi membuat duplikat baru; perubahan nama wajib lewat petugas + tercatat |
| Dua akun berbeda mendaftarkan orang yang sama | diblokir sidik jari (karena lintas akun) → petugas memilih "pakai RM yang ada" |

## 10. Daftar test yang harus ada

1. Dua pendaftaran nama + tanggal lahir sama (beda spasi/kapitalisasi) → **ditolak** database.
2. Tombol "Orang berbeda" → tersimpan dengan `salinan = 1`; pendaftaran ketiga tetap ditolak.
3. Mengubah tanggal lahir → `kode_identitas` ikut berubah.
4. Pasien walk-in tanpa akun (`user_id` NULL) tetap terkena penjaga.
5. Dua orang dengan tanggal lahir berbeda → lolos tanpa gangguan dan **tetap dua baris
   terpisah** (bukti tidak ada penggabungan otomatis).
6. Halaman data ganda tidak menampilkan data rekam medis dan tidak menampilkan sidik jari.
7. `activity_logs` memuat `keputusan_duplikat` untuk kedua pilihan petugas.

## 11. Urutan pengerjaan (bagian FASE 3)

1. Migrasi dua kolom + `UNIQUE`; tambahkan `IDENTITAS_HMAC_KEY` ke `.env.example` (kosong).
2. Model `Patient`: fungsi normalisasi, perhitungan sidik jari di `creating`/`updating`.
3. Halaman petugas: tangkap pelanggaran constraint → layar "Data ganda" + dua tombol +
   pencatatan audit; sekalian pencarian pasien (nama + tanggal lahir).
4. Test daftar di bagian 10, lalu perbarui `docs/DATABASE.md`, `docs/FEATURES.md`, dan
   baris keputusan di `docs/DECISIONS.md`.

## 12. Masih terbuka (perlu keputusan tim)

1. Kunci `IDENTITAS_HMAC_KEY`: dibuat per instalasi (disarankan) atau satu untuk tim?
2. Apakah tombol "Orang berbeda" boleh dipakai semua petugas, atau hanya supervisor?
3. Perubahan nama/tanggal lahir: hanya petugas, atau boleh pasien sendiri?
4. Apakah `salinan` dibatasi (mis. maksimal 3) supaya tidak disalahgunakan untuk
   mendaftarkan orang yang sama berkali-kali?
