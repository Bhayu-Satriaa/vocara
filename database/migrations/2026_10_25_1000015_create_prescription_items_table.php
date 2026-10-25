<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained('medicines');
            $table->integer('jumlah');
            $table->string('dosis', 100)->nullable();
            $table->string('aturan_pakai', 150)->nullable();
            $table->decimal('harga', 12, 2);                            // harga saat transaksi
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
