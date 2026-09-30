<?php

namespace App\Filament\Resources\Plans\Schemas;

use App\Filament\Support\TranslatableFields;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TranslatableFields::make('name', __('Nom'))->columnSpanFull(),
                TranslatableFields::make('description', __('Description'), fn (string $path) => Textarea::make($path)->rows(3), required: false)
                    ->columnSpanFull(),
                Section::make(__('Prix (MAD)'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('slug')
                            ->label(__('Slug'))
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (string $state) => Str::slug($state)),
                        TextInput::make('price_monthly')
                            ->label(__('Prix mensuel'))
                            ->required()->numeric()->minValue(0)->suffix('DH'),
                        TextInput::make('price_yearly')
                            ->label(__('Prix annuel'))
                            ->required()->numeric()->minValue(0)->suffix('DH'),
                    ]),
                Section::make(__('Fonctionnalités'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('features.max_photos')
                            ->label(__('Photos max.'))
                            ->required()->integer()->minValue(0)->default(3),
                        Toggle::make('features.show_stats')->label(__('Statistiques')),
                        Toggle::make('features.highlighted_card')->label(__('Carte mise en avant')),
                        Toggle::make('features.logo_and_cover')->label(__('Logo et couverture')),
                        Toggle::make('features.remove_competitor_ads')->label(__('Pas de sponsorisés concurrents sur sa fiche')),
                        Toggle::make('features.priority_support')->label(__('Modération prioritaire')),
                    ]),
                Section::make()
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('sort_order')->label(__('Ordre'))->integer()->default(0),
                        Toggle::make('is_default')->label(__('Formule par défaut (gratuite)')),
                        Toggle::make('is_active')->label(__('Active'))->default(true),
                    ]),
            ]);
    }
}
