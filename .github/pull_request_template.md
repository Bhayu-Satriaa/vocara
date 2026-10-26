## Apa yang ditambah/diubah

<!-- satu-dua kalimat: fitur atau perbaikan apa, untuk role siapa -->

## Cara menguji

<!-- langkah konkret supaya reviewer bisa mengulang, mis.
1. php artisan migrate:fresh --seed
2. login petugas@klinik.test / password
3. buka /petugas/scan, tempel token ...
-->

## Daftar periksa

- [ ] `php artisan test` lulus (baseline repo 42 test, tidak menurun)
- [ ] Satu fitur tuntas: migrasi → model → controller → route → view
- [ ] Tidak ada `.env`, `vendor/`, `public/build`, atau data pribadi ikut ter-commit
- [ ] Route baru dibatasi role yang benar (`middleware('role:...')`)
- [ ] Menyentuh data medis/QR → menulis jejak di `activity_logs` (atau `qr_scan_logs`)
- [ ] Kalau menyentuh file bersama (`routes/`, `layouts/app.blade.php`, `app.css`, `docs/`) → sudah dibahas di Discord

## Menunggu keputusan / belum dikerjakan

<!-- hal yang sengaja ditinggalkan, dan siapa/tim yang harus memutuskan -->
