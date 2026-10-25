<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\ActivityLog;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /** Setiap perubahan akun staf dicatat (aturan di AGENTS.md). */
    protected function afterSave(): void
    {
        ActivityLog::catat(
            'ubah_akun',
            "Ubah akun {$this->record->role}: {$this->record->email}",
            $this->record,
        );
    }
}
