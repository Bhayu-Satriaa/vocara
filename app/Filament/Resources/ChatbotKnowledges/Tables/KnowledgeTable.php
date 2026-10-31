<?php

namespace App\Filament\Resources\ChatbotKnowledges\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KnowledgeTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('kategori')
            ->columns([
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pertanyaan')
                    ->label('Pertanyaan')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('jawaban')
                    ->label('Jawaban')
                    ->limit(60)
                    ->wrap(),

                IconColumn::make('is_active')
                    ->label('Dipakai')
                    ->boolean(),

                TextColumn::make('editor.name')
                    ->label('Diubah oleh')
                    ->placeholder('-'),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([1 => 'Dipakai', 0 => 'Tidak dipakai']),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
