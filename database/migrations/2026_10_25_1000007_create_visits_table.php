<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->foreignId('poli_id')->constrained('polis');
            $table->foreignId('schedule_id')->nullable()->constrained('doctor_schedules')->onDelete('set null');
            $table->date('tgl_kunjungan');
            $table->time('jam');                                       // slot mulai kunjungan
            $table->unsignedSmallInteger('no_antrian')->nullable();
            $table->enum('status', ['booking', 'menunggu', 'diperiksa', 'farmasi', 'pembayaran', 'selesai', 'batal'])->default('booking');
            $table->unsignedTinyInteger('slot_aktif')->storedAs("if(status = 'batal', null, 1)");  // NULL saat batal, supaya slot bisa dipesan ulang
            $table->enum('sumber', ['online', 'walk_in', 'chatbot'])->default('online');
            $table->text('keluhan_awal')->nullable();
            $table->enum('checkin_metode', ['qr', 'manual'])->nullable();
            $table->dateTime('checkin_at')->nullable();
            $table->foreignId('checkin_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['doctor_id', 'tgl_kunjungan', 'jam', 'slot_aktif'], 'uq_slot_dokter');  // anti double-booking
            $table->unique(['poli_id', 'doctor_id', 'tgl_kunjungan', 'no_antrian'], 'uq_antrian');
            $table->index(['tgl_kunjungan', 'status'], 'idx_visit_tgl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
