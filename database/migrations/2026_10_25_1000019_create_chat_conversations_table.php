<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('set null');
            $table->enum('mode', ['teks', 'suara'])->default('teks');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
