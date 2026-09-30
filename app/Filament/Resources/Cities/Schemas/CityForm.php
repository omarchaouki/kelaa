<?php

namespace App\Filament\Resources\Cities\Schemas;

use App\Filament\Support\TranslatableFields;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CityForm
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
                        TextInput::make('slug')
                            ->label(__('Slug (URL)'))
                            ->required()
                            ->alphaDash()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (string $state) => Str::slug($state)),
                        TextInput::make('timezone')
                            ->label(__('Fuseau horaire'))
                            ->required()
                            ->default('Africa/Casablanca')
                            ->in(timezone_identifiers_list()),
                        TextInput::make('center_latitude')
                            ->label(__('Latitude du centre'))
                            ->required()->numeric()->minValue(-90)->maxValue(90),
                        TextInput::make('center_longitude')
                            ->label(__('Longitude du centre'))
                            ->required()->numeric()->minValue(-180)->maxValue(180),
                        TextInput::make('default_zoom')
                            ->label(__('Zoom par défaut'))
                            ->required()->integer()->minValue(5)->maxValue(19)->default(13),
                        Toggle::make('is_active')->label(__('Active'))->default(true),
                    ]),
            ]);
    }
}
