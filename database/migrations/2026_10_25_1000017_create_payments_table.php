<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained('visits')->onDelete('cascade');
            $table->foreignId('kasir_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('nomor_kwitansi', 30)->unique();
            $table->decimal('total_jasa', 12, 2)->default(0);
            $table->decimal('total_obat', 12, 2)->default(0);
            $table->decimal('total_bayar', 12, 2)->default(0);
            $table->enum('metode', ['tunai', 'transfer', 'qris', 'bpjs'])->nullable();
            $table->enum('status', ['belum_bayar', 'lunas'])->default('belum_bayar');
            $table->dateTime('dibayar_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
