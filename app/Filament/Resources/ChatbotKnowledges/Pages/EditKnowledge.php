<?php

namespace App\Filament\Resources\ChatbotKnowledges\Pages;

use App\Filament\Resources\ChatbotKnowledges\ChatbotKnowledgeResource;
use Filament\Resources\Pages\EditRecord;

class EditKnowledge extends EditRecord
{
    protected static string $resource = ChatbotKnowledgeResource::class;

    /** Catat siapa yang terakhir mengubah pengetahuan (kolom `updated_by`). */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
