<?php

namespace App\Filament\Resources\ChatbotKnowledges\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class KnowledgeForm
{
    /** Isi basis pengetahuan: pertanyaan pasien dan jawabannya. */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kategori')
                    ->label('Kategori')
                    ->required()
                    ->maxLength(50)
                    ->helperText('Mis. Pendaftaran, Kartu QR, Layanan, Biaya.'),

                TextInput::make('pertanyaan')
                    ->label('Pertanyaan pasien')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Tulis seperti pasien bertanya, mis. "syarat daftar berobat apa saja".'),

                Textarea::make('jawaban')
                    ->label('Jawaban')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Dipakai chatbot')
                    ->default(true)
                    ->helperText('Matikan kalau jawabannya sudah tidak berlaku.'),
            ]);
    }
}
