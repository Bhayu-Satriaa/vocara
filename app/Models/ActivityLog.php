<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    // Skema hanya punya created_at.
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'aksi', 'subjek_tipe', 'subjek_id', 'deskripsi', 'ip_address'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat aktivitas. Rekam medis dan scan QR wajib punya jejak di sini
     * (aturan di AGENTS.md).
     */
    public static function catat(string $aksi, ?string $deskripsi = null, ?Model $subjek = null): self
    {
        return static::create([
            'user_id' => Auth::id(),
            'aksi' => $aksi,
            'subjek_tipe' => $subjek ? $subjek::class : null,
            'subjek_id' => $subjek?->getKey(),
            'deskripsi' => $deskripsi,
            'ip_address' => Request::ip(),
        ]);
    }
}
