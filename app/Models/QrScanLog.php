<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrScanLog extends Model
{
    // Skema hanya punya created_at, jadi updated_at dimatikan.
    public const UPDATED_AT = null;

    protected $fillable = ['patient_id', 'scanned_by', 'konteks'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
