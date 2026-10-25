<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    // Skema hanya punya created_at.
    public const UPDATED_AT = null;

    protected $fillable = ['conversation_id', 'pengirim', 'isi', 'tipe_input', 'intent', 'dibacakan_tts'];

    protected function casts(): array
    {
        return ['dibacakan_tts' => 'boolean'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }
}
