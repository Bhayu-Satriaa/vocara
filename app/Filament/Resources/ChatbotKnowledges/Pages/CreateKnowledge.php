<?php

namespace App\Filament\Resources\ChatbotKnowledges\Pages;

use App\Filament\Resources\ChatbotKnowledges\ChatbotKnowledgeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledge extends CreateRecord
{
    protected static string $resource = ChatbotKnowledgeResource::class;

    /** Catat siapa yang menambah pengetahuan (kolom `updated_by`). */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
