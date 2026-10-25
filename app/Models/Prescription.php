<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    public const STATUS = ['menunggu', 'disiapkan', 'selesai'];

    protected $attributes = ['status' => 'menunggu'];

    protected $fillable = ['visit_id', 'doctor_id', 'apoteker_id', 'status', 'catatan', 'diserahkan_at'];

    protected function casts(): array
    {
        return ['diserahkan_at' => 'datetime'];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function apoteker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'apoteker_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(MedicineStockMovement::class);
    }
}
