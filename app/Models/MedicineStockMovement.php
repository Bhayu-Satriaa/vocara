<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineStockMovement extends Model
{
    // Skema hanya punya created_at.
    public const UPDATED_AT = null;

    protected $fillable = [
        'medicine_id', 'tipe', 'jumlah', 'tgl_kedaluwarsa', 'prescription_id',
        'keterangan', 'created_by',
    ];

    protected function casts(): array
    {
        return ['tgl_kedaluwarsa' => 'date'];
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
