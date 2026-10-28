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
     * Nilai NIK lama WAJIB dibackup lebih dulu (langkah manual sebelum migrate), mis.
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
        // nullable, lalu pulihkan isinya dari patients_nik_backup (lihat docs/DATABASE.md).
        Schema::table('patients', function (Blueprint $table) {
            $table->char('nik', 16)->nullable()->unique()->after('no_rm');
        });
    }
};
