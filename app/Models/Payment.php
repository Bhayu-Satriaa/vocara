<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $attributes = ['status' => 'belum_bayar'];

    protected $fillable = [
        'visit_id', 'kasir_id', 'nomor_kwitansi', 'total_jasa', 'total_obat',
        'total_bayar', 'metode', 'status', 'dibayar_at',
    ];

    protected function casts(): array
    {
        return [
            'total_jasa' => 'decimal:2',
            'total_obat' => 'decimal:2',
            'total_bayar' => 'decimal:2',
            'dibayar_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    public function lunas(): bool
    {
        return $this->status === 'lunas';
    }
}
