<?php

namespace App\Filament\Resources\Cities\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['neighborhoods', 'businesses']))
            ->columns([
                TextColumn::make('name')->label(__('Nom')),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('neighborhoods_count')->label(__('Quartiers'))->numeric(),
                TextColumn::make('businesses_count')->label(__('Établissements'))->numeric(),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
