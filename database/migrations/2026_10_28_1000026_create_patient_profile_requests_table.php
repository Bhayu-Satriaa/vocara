<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel penampung permohonan/pengajuan profil pasien.
     * Sebelum disetujui admin, data berada di sini dan belum masuk ke tabel patients
     * (belum memiliki No Rekam Medis maupun QR Code).
     */
    public function up(): void
    {
        Schema::create('patient_profile_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nama_lengkap', 100);
            $table->enum('hubungan_keluarga', ['diri sendiri', 'pasangan', 'anak', 'orang tua', 'saudara', 'lainnya'])->default('diri sendiri');
            $table->string('no_wa', 20);
            $table->date('tgl_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->text('alamat')->nullable();
            $table->enum('golongan_darah', ['A', 'B', 'AB', 'O', '-'])->default('-');
            $table->text('alergi')->nullable();
            $table->boolean('is_utama')->default(false);
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_profile_requests');
    }
};

