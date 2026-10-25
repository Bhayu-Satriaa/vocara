<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_record_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained('medical_records')->onDelete('cascade');
            $table->foreignId('disease_id')->constrained('diseases');
            $table->enum('tipe', ['primer', 'sekunder'])->default('primer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_record_diagnoses');
    }
};
