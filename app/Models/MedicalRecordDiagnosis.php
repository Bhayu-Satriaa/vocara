<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecordDiagnosis extends Model
{
    public $timestamps = false;

    protected $fillable = ['medical_record_id', 'disease_id', 'tipe'];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }
}
