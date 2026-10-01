<?php

namespace App\Filament\Resources\PlacementRates\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlacementRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['city', 'category']))
            ->columns([
                TextColumn::make('city.name')->label(__('Ville'))->placeholder(__('Toutes')),
                TextColumn::make('category.name')->label(__('Catégorie'))->placeholder(__('Toutes')),
                TextColumn::make('position')->label(__('Position')),
                TextColumn::make('price_week')->label(__('Semaine'))->money('MAD'),
                TextColumn::make('price_month')->label(__('Mois'))->money('MAD'),
                TextColumn::make('neighborhood_percent')->label(__('Quartier'))->suffix(' %'),
                IconColumn::make('is_active')->label(__('En vente'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
