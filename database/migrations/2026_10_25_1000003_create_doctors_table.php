<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->foreignId('poli_id')->constrained('polis');
            $table->string('no_str', 50)->nullable();
            $table->string('spesialisasi', 100)->nullable();
            $table->decimal('tarif_konsultasi', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
