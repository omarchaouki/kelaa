<?php

namespace App\Filament\Resources\SearchSynonyms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SearchSynonymsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('term')
            ->columns([
                TextColumn::make('term')->label(__('Terme'))->searchable(),
                TextColumn::make('synonyms')->label(__('Synonymes'))->badge()->separator(','),
                TextColumn::make('locale')->label(__('Langue'))->placeholder(__('Toutes')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
