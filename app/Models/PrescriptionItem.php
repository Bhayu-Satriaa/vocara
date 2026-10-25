<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    public $timestamps = false;

    // `harga` adalah harga saat transaksi.
    protected $fillable = ['prescription_id', 'medicine_id', 'jumlah', 'dosis', 'aturan_pakai', 'harga'];

    protected function casts(): array
    {
        return ['harga' => 'decimal:2'];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
