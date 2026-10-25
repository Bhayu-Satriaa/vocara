<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends Model
{
    public $timestamps = false;

    protected $fillable = ['doctor_id', 'hari', 'jam_mulai', 'jam_selesai', 'durasi_slot', 'kuota', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
