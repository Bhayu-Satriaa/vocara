<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Patient extends Model
{
    /** Pilihan hubungan data diri ini dengan pemilik akun (multi-profile). */
    public const HUBUNGAN = ['diri sendiri', 'pasangan', 'anak', 'orang tua', 'saudara', 'lainnya'];

    protected $fillable = [
        'user_id', 'no_rm', 'nama_lengkap', 'hubungan_keluarga', 'no_wa',
        'tgl_lahir', 'jenis_kelamin', 'alamat', 'golongan_darah', 'alergi', 'qr_token',
        'verification_status', 'verified_by', 'verified_at',
    ];

    /** Samakan dengan default di database, supaya nilai terbaca benar sebelum refresh(). */
    protected $attributes = [
        'is_utama' => false,
        'golongan_darah' => '-',
        'hubungan_keluarga' => 'diri sendiri',
        'verification_status' => 'belum',
    ];

    protected function casts(): array
    {
        return [
            'is_utama' => 'boolean',
            'tgl_lahir' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Patient $patient) {
            // Keputusan proyek: QR dibuat otomatis saat pasien terdaftar, isinya token acak.
            $patient->qr_token ??= (string) Str::uuid();

            // Nomor rekam medis adalah satu-satunya pembeda data diri pasien, jadi selalu
            // diisi otomatis untuk semua jalur pembuatan (pasien online maupun walk-in).
            $patient->no_rm ??= static::nomorRekamMedisBaru();
        });

        // Data diri pertama sebuah akun langsung jadi yang dipakai (profil utama).
        static::created(function (Patient $patient) {
            if ($patient->user_id && ! static::query()->where('user_id', $patient->user_id)->where('is_utama', true)->exists()) {
                $patient->jadikanUtama();
            }
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

    /** Urut kartu profil: data diri yang sedang dipakai paling depan, lalu sesuai waktu dibuat. */
    public function scopeTerurut(Builder $query): Builder
    {
        return $query->orderByDesc('is_utama')->orderBy('id');
    }

    /**
     * Jadikan data diri ini yang dipakai akunnya (kartu QR & booking). Hanya boleh satu
     * per akun, jadi data diri lama dilepas lebih dulu dalam transaksi yang sama.
     */
    public function jadikanUtama(): void
    {
        DB::transaction(function () {
            static::query()
                ->where('user_id', $this->user_id)
                ->where('id', '!=', $this->getKey())
                ->update(['is_utama' => false]);

            $this->forceFill(['is_utama' => true])->save();
        });
    }

    /** Nomor rekam medis berurutan: RM-000001, RM-000002, ... */
    public static function nomorRekamMedisBaru(): string
    {
        $urut = (int) static::query()->max('id') + 1;

        do {
            $nomor = 'RM-'.str_pad((string) $urut, 6, '0', STR_PAD_LEFT);
            $urut++;
        } while (static::query()->where('no_rm', $nomor)->exists());

        return $nomor;
    }
}
