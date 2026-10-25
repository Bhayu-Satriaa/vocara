<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triage_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->onDelete('cascade');
            $table->foreignId('visit_id')->nullable()->constrained('visits')->onDelete('set null');
            $table->text('ringkasan_keluhan');
            $table->foreignId('poli_disarankan')->nullable()->constrained('polis')->onDelete('set null');
            $table->enum('tingkat_urgensi', ['rendah', 'sedang', 'tinggi'])->default('rendah');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triage_results');
    }
};
