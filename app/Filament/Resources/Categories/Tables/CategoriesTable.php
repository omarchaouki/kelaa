<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Tree order: each group followed by its children. Drag and drop keeps
            // the relative order of siblings, which is all the front end relies on.
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('parent')
                ->withCount('businesses')
                ->orderByRaw('coalesce((select tree_parent.sort_order from categories as tree_parent where tree_parent.id = categories.parent_id), categories.sort_order)')
                ->orderByRaw('coalesce(categories.parent_id, categories.id)')
                ->orderByRaw('categories.parent_id is not null')
                ->orderBy('categories.sort_order'))
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Nom'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%")),
                TextColumn::make('parent.name')->label(__('Parente'))->placeholder('—'),
                TextColumn::make('slug')->searchable()->toggleable(),
                TextColumn::make('businesses_count')->label(__('Établissements'))->numeric(),
                IconColumn::make('is_emergency')->label(__('Urgence'))->boolean(),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label(__('Parente'))
                    ->relationship('parent', 'slug', fn (Builder $query) => $query->whereNull('parent_id')),
                TernaryFilter::make('is_active')->label(__('Active')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
