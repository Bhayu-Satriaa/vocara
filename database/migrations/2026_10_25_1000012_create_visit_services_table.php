<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services');
            $table->unsignedSmallInteger('qty')->default(1);
            $table->decimal('harga', 12, 2);                            // harga saat transaksi
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_services');
    }
};
