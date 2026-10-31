<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multi-profile: satu akun pasien boleh memegang lebih dari satu data diri
     * (mis. anak atau pasangan). Karena itu patients.user_id tidak lagi UNIQUE —
     * profil yang sedang dipakai untuk kartu QR & booking ditandai is_utama,
     * dan hubungan_keluarga menjelaskan siapa pemilik data diri tersebut.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->boolean('is_utama')->default(false)->after('user_id');
            $table->enum('hubungan_keluarga', ['diri sendiri', 'pasangan', 'anak', 'orang tua', 'saudara', 'lainnya'])
                ->default('diri sendiri')
                ->after('nama_lengkap');

            // Indeks pengganti dibuat sebelum UNIQUE dilepas: InnoDB tetap butuh indeks
            // berkolom pertama user_id untuk fk_patients_user.
            $table->index(['user_id', 'is_utama'], 'idx_patients_user');
            $table->dropUnique('patients_user_id_unique');
        });

        // Data lama cuma punya satu data diri per akun: yang terdahulu jadi profil aktif.
        DB::statement(<<<'SQL'
            update patients
            set is_utama = 1
            where id in (
                select id from (
                    select min(id) as id from patients where user_id is not null group by user_id
                ) terdahulu
            )
        SQL);
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('idx_patients_user');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['is_utama', 'hubungan_keluarga']);
            $table->unique('user_id');
        });
    }
};
