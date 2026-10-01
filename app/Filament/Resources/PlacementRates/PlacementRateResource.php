<?php

namespace App\Filament\Resources\PlacementRates;

use App\Filament\Resources\PlacementRates\Pages\CreatePlacementRate;
use App\Filament\Resources\PlacementRates\Pages\EditPlacementRate;
use App\Filament\Resources\PlacementRates\Pages\ListPlacementRates;
use App\Filament\Resources\PlacementRates\Schemas\PlacementRateForm;
use App\Filament\Resources\PlacementRates\Tables\PlacementRatesTable;
use App\Models\PlacementRate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PlacementRateResource extends Resource
{
    protected static ?string $model = PlacementRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 45;

    public static function getNavigationGroup(): ?string
    {
        return __('Monétisation');
    }

    public static function getModelLabel(): string
    {
        return __('tarif sponsorisé');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tarifs sponsorisés');
    }

    public static function form(Schema $schema): Schema
    {
        return PlacementRateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlacementRatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlacementRates::route('/'),
            'create' => CreatePlacementRate::route('/create'),
            'edit' => EditPlacementRate::route('/{record}/edit'),
        ];
    }
}
