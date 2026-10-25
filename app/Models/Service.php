<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    public $timestamps = false;

    protected $fillable = ['nama', 'kategori', 'tarif', 'is_active'];

    protected function casts(): array
    {
        return ['tarif' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function visitServices(): HasMany
    {
        return $this->hasMany(VisitService::class);
    }
}
