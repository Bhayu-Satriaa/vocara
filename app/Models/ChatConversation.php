<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatConversation extends Model
{
    // Waktu percakapan disimpan di kolom sendiri (started_at/ended_at).
    public $timestamps = false;

    protected $fillable = ['user_id', 'patient_id', 'mode', 'started_at', 'ended_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function triageResult(): HasOne
    {
        return $this->hasOne(TriageResult::class);
    }
}
