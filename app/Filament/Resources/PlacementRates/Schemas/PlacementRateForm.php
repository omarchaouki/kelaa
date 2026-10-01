<?php

namespace App\Filament\Resources\PlacementRates\Schemas;

use App\Models\Category;
use App\Models\City;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlacementRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('city_id')->label(__('Ville'))
                ->options(fn () => City::query()->orderBy('slug')->get()->mapWithKeys(fn (City $c) => [$c->id => $c->name]))
                ->placeholder(__('Toutes les villes')),
            Select::make('category_id')->label(__('Catégorie'))
                ->options(fn () => Category::query()->whereNotNull('parent_id')->orderBy('slug')->get()->mapWithKeys(fn (Category $c) => [$c->id => $c->name]))
                ->placeholder(__('Toutes les catégories'))
                ->searchable(),
            Select::make('position')->label(__('Position'))->options([1 => '1', 2 => '2', 3 => '3'])->required(),
            TextInput::make('price_week')->label(__('Prix par semaine'))->numeric()->minValue(0)->required()->suffix('DH'),
            TextInput::make('price_month')->label(__('Prix par mois'))->numeric()->minValue(0)->required()->suffix('DH'),
            TextInput::make('neighborhood_percent')->label(__('Prix quartier (% du prix ville)'))->integer()->minValue(1)->maxValue(100)->default(60)->required(),
            Toggle::make('is_active')->label(__('En vente'))->default(true),
        ]);
    }
}
