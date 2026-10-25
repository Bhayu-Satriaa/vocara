<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    public $timestamps = false;

    protected $fillable = ['kode', 'nama', 'satuan', 'harga', 'stok', 'stok_minimum', 'is_active'];

    protected function casts(): array
    {
        return ['harga' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(MedicineStockMovement::class);
    }

    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    /** Obat yang stoknya sudah menyentuh batas minimum (untuk dashboard admin). */
    public function scopeStokMenipis($query)
    {
        return $query->whereColumn('stok', '<=', 'stok_minimum');
    }
}
