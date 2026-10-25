<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visit extends Model
{
    /** Alur status kunjungan (lihat docs/FEATURES.md). */
    public const STATUS = ['booking', 'menunggu', 'diperiksa', 'farmasi', 'pembayaran', 'selesai', 'batal'];

    /** Samakan dengan default di database, supaya nilai terbaca benar sebelum refresh(). */
    protected $attributes = [
        'status' => 'booking',
        'sumber' => 'online',
    ];

    protected $fillable = [
        'patient_id', 'doctor_id', 'poli_id', 'schedule_id', 'tgl_kunjungan', 'jam',
        'no_antrian', 'status', 'sumber', 'keluhan_awal', 'checkin_metode', 'checkin_at',
        'checkin_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_kunjungan' => 'date',
            'checkin_at' => 'datetime',
        ];
    }

    /** Kunjungan yang masih menempati slot dokter (bukan batal). */
    public function scopeAktif($query)
    {
        return $query->where('status', '!=', 'batal');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DoctorSchedule::class, 'schedule_id');
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(VisitService::class);
    }

    public function dibatalkan(): bool
    {
        return $this->status === 'batal';
    }
}
