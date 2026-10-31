<?php

namespace App\Filament\Resources\ChatbotKnowledges;

use App\Filament\Resources\ChatbotKnowledges\Pages\CreateKnowledge;
use App\Filament\Resources\ChatbotKnowledges\Pages\EditKnowledge;
use App\Filament\Resources\ChatbotKnowledges\Pages\ListKnowledge;
use App\Filament\Resources\ChatbotKnowledges\Schemas\KnowledgeForm;
use App\Filament\Resources\ChatbotKnowledges\Tables\KnowledgeTable;
use App\Models\ChatbotKnowledge;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Basis pengetahuan chatbot untuk ADMIN.
 *
 * Isinya hanya konten (kategori, pertanyaan, jawaban, aktif/tidak) — pengaturan teknis
 * bot (model, system prompt, kunci API) sengaja TIDAK ada di sini, itu urusan tim IT
 * lewat .env/kode. Lihat docs/SPEC-CHATBOT.md bagian 3.
 */
class ChatbotKnowledgeResource extends Resource
{
    protected static ?string $model = ChatbotKnowledge::class;

    /**
     * Slug ditulis eksplisit: Filament menurunkan slug dari nama kelas, dan karena kata
     * "knowledge" tidak berjamak (uncountable), hasil otomatisnya jadi
     * "chatbot-knowledges/chatbot-knowledge" — bukan alamat yang enak dibaca admin.
     */
    protected static ?string $slug = 'chatbot-knowledge';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 3;

    /** Hanya admin yang boleh mengubah isi basis pengetahuan chatbot. */
    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return (bool) $user?->punyaRole('admin');
    }

    public static function getModelLabel(): string
    {
        return 'pengetahuan chatbot';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pengetahuan chatbot';
    }

    public static function form(Schema $schema): Schema
    {
        return KnowledgeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KnowledgeTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledge::route('/'),
            'create' => CreateKnowledge::route('/create'),
            'edit' => EditKnowledge::route('/{record}/edit'),
        ];
    }
}
