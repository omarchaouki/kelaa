<?php

namespace App\Filament\Resources\Neighborhoods;

use App\Filament\Resources\Neighborhoods\Pages\CreateNeighborhood;
use App\Filament\Resources\Neighborhoods\Pages\EditNeighborhood;
use App\Filament\Resources\Neighborhoods\Pages\ListNeighborhoods;
use App\Filament\Resources\Neighborhoods\Schemas\NeighborhoodForm;
use App\Filament\Resources\Neighborhoods\Tables\NeighborhoodsTable;
use App\Models\Neighborhood;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NeighborhoodResource extends Resource
{
    protected static ?string $model = Neighborhood::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'slug';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Catalogue');
    }

    public static function getModelLabel(): string
    {
        return __('quartier');
    }

    public static function getPluralModelLabel(): string
    {
        return __('quartiers');
    }

    public static function form(Schema $schema): Schema
    {
        return NeighborhoodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NeighborhoodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNeighborhoods::route('/'),
            'create' => CreateNeighborhood::route('/create'),
            'edit' => EditNeighborhood::route('/{record}/edit'),
        ];
    }
}
