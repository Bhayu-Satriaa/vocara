<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_STAF = ['admin', 'petugas', 'dokter', 'apoteker'];

    /** Pasien bukan staf: akunnya dibuat sendiri lewat Google, bukan oleh admin. */
    public const ROLE = [...self::ROLE_STAF, 'pasien'];

    /** Samakan dengan default di database, supaya nilai terbaca benar sebelum refresh(). */
    protected $attributes = ['is_active' => true];

    protected $fillable = ['name', 'email', 'google_id', 'avatar', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean'];
    }

    /** Biodata pasien yang dipegang akun ini (NULL untuk pasien walk-in). */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    /** Profil dokter, terisi hanya untuk role `dokter`. */
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    public function punyaRole(string ...$role): bool
    {
        return in_array($this->role, $role, true);
    }

    /**
     * Profil pasien dianggap lengkap kalau biodatanya sudah ada dan nomor WA terisi.
     * Nomor WA belum diverifikasi OTP — verifikasi nyata dilakukan petugas dengan KTP.
     */
    public function profilLengkap(): bool
    {
        return $this->patients()
            ->whereNotNull('no_wa')
            ->where('no_wa', '!=', '')
            ->exists();
    }

    /** Staf = semua role selain pasien; hanya staf yang login dengan password. */
    public function staf(): bool
    {
        return $this->role !== 'pasien';
    }

    /** Hanya staf aktif yang boleh masuk panel Filament. Pasien tidak pernah. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->staf() && $this->is_active;
    }
}
