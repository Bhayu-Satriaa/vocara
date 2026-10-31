<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientProfileRequest extends Model
{
    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'hubungan_keluarga',
        'no_wa',
        'tgl_lahir',
        'jenis_kelamin',
        'alamat',
        'golongan_darah',
        'alergi',
        'is_utama',
        'status',
        'catatan_admin',
        'approved_by',
        'approved_at',
        'patient_id',
    ];

    protected $attributes = [
        'is_utama' => false,
        'golongan_darah' => '-',
        'hubungan_keluarga' => 'diri sendiri',
        'status' => 'menunggu',
    ];

    protected function casts(): array
    {
        return [
            'is_utama' => 'boolean',
            'tgl_lahir' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', 'menunggu');
    }
}

