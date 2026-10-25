<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('aksi', 50);                                // login, lihat_rekam_medis, ubah_data, dll
            $table->string('subjek_tipe', 50)->nullable();
            $table->unsignedBigInteger('subjek_id')->nullable();
            $table->string('deskripsi', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at'], 'idx_log_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
