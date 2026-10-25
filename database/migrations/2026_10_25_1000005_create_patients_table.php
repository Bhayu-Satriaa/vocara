<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->onDelete('set null');  // NULL jika didaftarkan petugas (walk-in)
            $table->string('no_rm', 20)->unique();
            $table->char('nik', 16)->unique();
            $table->string('nama_lengkap', 100);
            $table->string('no_wa', 20);
            $table->date('tgl_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->text('alamat')->nullable();
            $table->enum('golongan_darah', ['A', 'B', 'AB', 'O', '-'])->default('-');
            $table->text('alergi')->nullable();
            $table->char('qr_token', 36)->unique();                    // UUID acak, isi QR (bukan NIK/data medis)
            $table->enum('verification_status', ['belum', 'terverifikasi'])->default('belum');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
