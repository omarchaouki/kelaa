<?php

namespace App\Filament\Resources\BannedWords\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BannedWordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('word')
            ->columns([
                TextColumn::make('word')->label(__('Mot ou expression'))->searchable(),
                TextColumn::make('created_at')->label(__('Ajouté le'))->date('d/m/Y'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
