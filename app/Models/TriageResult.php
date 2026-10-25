<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriageResult extends Model
{
    // Skema hanya punya created_at.
    public const UPDATED_AT = null;

    public const URGENSI = ['rendah', 'sedang', 'tinggi'];

    protected $fillable = ['conversation_id', 'visit_id', 'ringkasan_keluhan', 'poli_disarankan', 'tingkat_urgensi'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class, 'poli_disarankan');
    }
}
