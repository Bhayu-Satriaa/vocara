<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NIK dihapus dari sistem. Pembeda antar data diri pasien hanya Nomor Rekam Medis
     * (`patients.no_rm`, sudah NOT NULL + UNIQUE), jadi kolom nik beserta unique index-nya
     * tidak dipakai lagi.
     *
     * Backup nilai NIK bersifat opsional dan **tidak dilakukan** di proyek ini: menyimpan
     * NIK kembali di tabel lain berlawanan dengan keputusan privasi. Kalau sebuah instalasi
     * tetap butuh nilainya (mis. data produksi), buat backup SEBELUM menjalankan migrasi:
     *
     * CREATE TABLE patients_nik_backup AS
     *   SELECT id, no_rm, nama_lengkap, nik, updated_at FROM patients;
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['nik']);
        });

        // Dilepas terpisah: SQLite tidak bisa drop kolom yang masih terindeks.
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('nik');
        });
    }

    public function down(): void
    {
        // Nilai NIK tidak dapat direkonstruksi otomatis. Kembalikan kolom sebagai
        // nullable; isinya hanya bisa diisi kalau instalasi ini punya backup sendiri
        // (lihat docs/DATABASE.md — proyek ini tidak membuat backup).
        Schema::table('patients', function (Blueprint $table) {
            $table->char('nik', 16)->nullable()->unique()->after('no_rm');
        });
    }
};
