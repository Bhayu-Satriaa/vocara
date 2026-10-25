<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Patient extends Model
{
    protected $fillable = [
        'user_id', 'no_rm', 'nik', 'nama_lengkap', 'no_wa', 'tgl_lahir', 'jenis_kelamin',
        'alamat', 'golongan_darah', 'alergi', 'qr_token', 'verification_status',
        'verified_by', 'verified_at',
    ];

    /** Samakan dengan default di database, supaya nilai terbaca benar sebelum refresh(). */
    protected $attributes = [
        'golongan_darah' => '-',
        'verification_status' => 'belum',
    ];

    protected function casts(): array
    {
        return [
            'tgl_lahir' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Keputusan proyek: QR dibuat otomatis saat pasien terdaftar, isinya token acak.
        static::creating(function (Patient $patient) {
            $patient->qr_token ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function qrScanLogs(): HasMany
    {
        return $this->hasMany(QrScanLog::class);
    }

    public function sudahTerverifikasi(): bool
    {
        return $this->verification_status === 'terverifikasi';
    }
}
