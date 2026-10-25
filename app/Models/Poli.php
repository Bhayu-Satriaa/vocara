<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poli extends Model
{
    // Tabel ini tidak punya created_at/updated_at di skema.
    public $timestamps = false;

    protected $fillable = ['nama', 'deskripsi', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
