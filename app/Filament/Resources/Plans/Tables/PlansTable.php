<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('Nom')),
                TextColumn::make('price_monthly')->label(__('Mensuel'))->money('MAD'),
                TextColumn::make('price_yearly')->label(__('Annuel'))->money('MAD'),
                TextColumn::make('features.max_photos')->label(__('Photos max.')),
                IconColumn::make('is_default')->label(__('Par défaut'))->boolean(),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
