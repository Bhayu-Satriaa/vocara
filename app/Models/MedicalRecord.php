<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecord extends Model
{
    protected $fillable = [
        'visit_id', 'subjective', 'objective', 'assessment', 'plan', 'tekanan_darah',
        'suhu', 'nadi', 'napas', 'berat_badan', 'tinggi_badan', 'diinput_via_suara',
    ];

    protected function casts(): array
    {
        return [
            'diinput_via_suara' => 'boolean',
            'suhu' => 'decimal:1',
            'berat_badan' => 'decimal:2',
            'tinggi_badan' => 'decimal:2',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(MedicalRecordDiagnosis::class);
    }

    public function speechLogs(): HasMany
    {
        return $this->hasMany(SpeechLog::class);
    }
}
