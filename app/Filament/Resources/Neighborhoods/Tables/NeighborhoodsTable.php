<?php

namespace App\Filament\Resources\Neighborhoods\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NeighborhoodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('city')->withCount('businesses'))
            ->defaultSort('slug')
            ->columns([
                TextColumn::make('name')->label(__('Nom')),
                TextColumn::make('city.name')->label(__('Ville')),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('businesses_count')->label(__('Établissements'))->numeric(),
                IconColumn::make('is_active')->label(__('Actif'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('city_id')->label(__('Ville'))->relationship('city', 'slug'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
