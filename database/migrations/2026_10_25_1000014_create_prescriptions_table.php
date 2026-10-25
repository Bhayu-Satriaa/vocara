<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained('visits')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->foreignId('apoteker_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['menunggu', 'disiapkan', 'selesai'])->default('menunggu');
            $table->text('catatan')->nullable();
            $table->dateTime('diserahkan_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
