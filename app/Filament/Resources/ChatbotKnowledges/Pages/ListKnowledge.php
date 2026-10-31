<?php

namespace App\Filament\Resources\ChatbotKnowledges\Pages;

use App\Filament\Resources\ChatbotKnowledges\ChatbotKnowledgeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledge extends ListRecords
{
    protected static string $resource = ChatbotKnowledgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah pengetahuan'),
        ];
    }
}
