<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'poli_id', 'no_str', 'spesialisasi', 'tarif_konsultasi'];

    protected function casts(): array
    {
        return ['tarif_konsultasi' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
