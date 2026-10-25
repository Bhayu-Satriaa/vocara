<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disease extends Model
{
    public $timestamps = false;

    protected $fillable = ['kode_icd10', 'nama'];

    public function diagnoses(): HasMany
    {
        return $this->hasMany(MedicalRecordDiagnosis::class);
    }
}
