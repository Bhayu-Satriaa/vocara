<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeechLog extends Model
{
    // Skema hanya punya created_at.
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'konteks', 'tipe', 'teks', 'medical_record_id', 'durasi_detik'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
