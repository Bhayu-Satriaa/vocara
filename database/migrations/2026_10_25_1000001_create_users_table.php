<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('google_id', 100)->nullable()->unique();   // login Google (pasien)
            $table->string('avatar', 255)->nullable();
            $table->string('password', 255)->nullable();              // diisi untuk staf, NULL untuk pasien
            $table->enum('role', ['admin', 'petugas', 'dokter', 'apoteker', 'pasien']);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
