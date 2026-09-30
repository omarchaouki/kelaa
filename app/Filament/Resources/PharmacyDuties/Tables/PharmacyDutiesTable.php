<?php

namespace App\Filament\Resources\PharmacyDuties\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PharmacyDutiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('business'))
            ->defaultSort('starts_at')
            ->columns([
                TextColumn::make('date')->label(__('Date'))->date('d/m/Y')->sortable(),
                TextColumn::make('business.name')->label(__('Pharmacie'))->searchable(),
                TextColumn::make('starts_at')->label(__('Début'))->dateTime('d/m H:i'),
                TextColumn::make('ends_at')->label(__('Fin'))->dateTime('d/m H:i'),
            ])
            ->filters([
                Filter::make('upcoming')
                    ->label(__('À venir'))
                    ->default()
                    ->query(fn (Builder $query) => $query->where('ends_at', '>', now())),
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
