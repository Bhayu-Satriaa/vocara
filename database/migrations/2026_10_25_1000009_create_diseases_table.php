<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diseases', function (Blueprint $table) {
            $table->id();
            $table->string('kode_icd10', 10)->unique();
            $table->string('nama', 200);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diseases');
    }
};
