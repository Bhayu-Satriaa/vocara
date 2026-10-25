<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->onDelete('cascade');
            $table->enum('pengirim', ['pengguna', 'bot']);
            $table->text('isi');
            $table->enum('tipe_input', ['teks', 'suara'])->default('teks');
            $table->string('intent', 50)->nullable();                  // faq, booking, triase
            $table->boolean('dibacakan_tts')->default(false);          // 1 jika dibacakan text-to-speech
            $table->timestamp('created_at')->nullable();

            $table->index(['conversation_id', 'created_at'], 'idx_msg_conv');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
