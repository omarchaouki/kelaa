<?php

namespace App\Filament\Resources\Pages\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('slug')
            ->columns([
                TextColumn::make('title')->label(__('Titre')),
                TextColumn::make('slug')->searchable(),
                IconColumn::make('is_published')->label(__('Publiée'))->boolean(),
                TextColumn::make('updated_at')->label(__('Modifiée le'))->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
