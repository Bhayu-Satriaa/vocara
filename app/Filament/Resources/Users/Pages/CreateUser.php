<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\ActivityLog;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /** Aturan proyek: pembuatan akun staf wajib punya jejak di activity_logs. */
    protected function afterCreate(): void
    {
        ActivityLog::catat(
            'tambah_akun',
            "Buat akun {$this->record->role}: {$this->record->email}",
            $this->record,
        );
    }
}
