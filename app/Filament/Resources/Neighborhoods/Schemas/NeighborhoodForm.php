<?php

namespace App\Filament\Resources\Neighborhoods\Schemas;

use App\Filament\Support\TranslatableFields;
use App\Models\City;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class NeighborhoodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TranslatableFields::make('name', __('Nom'))->columnSpanFull(),
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('city_id')
                            ->label(__('Ville'))
                            ->relationship('city', 'slug')
                            ->getOptionLabelFromRecordUsing(fn (City $record) => $record->name)
                            ->required()
                            ->live()
                            ->preload(),
                        TextInput::make('slug')
                            ->label(__('Slug (URL)'))
                            ->required()
                            ->alphaDash()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('city_id', $get('city_id')))
                            ->dehydrateStateUsing(fn (string $state) => Str::slug($state)),
                        TextInput::make('latitude')
                            ->label(__('Latitude du centre'))
                            ->numeric()->minValue(-90)->maxValue(90),
                        TextInput::make('longitude')
                            ->label(__('Longitude du centre'))
                            ->numeric()->minValue(-180)->maxValue(180),
                        Toggle::make('is_active')->label(__('Actif'))->default(true),
                    ]),
            ]);
    }
}
