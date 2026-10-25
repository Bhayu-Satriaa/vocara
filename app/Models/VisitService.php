<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitService extends Model
{
    public $timestamps = false;

    // `harga` adalah harga saat transaksi, bukan tarif master.
    protected $fillable = ['visit_id', 'service_id', 'qty', 'harga'];

    protected function casts(): array
    {
        return ['harga' => 'decimal:2'];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
