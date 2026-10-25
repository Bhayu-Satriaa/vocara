<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotKnowledge extends Model
{
    protected $fillable = ['kategori', 'pertanyaan', 'jawaban', 'is_active', 'updated_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
