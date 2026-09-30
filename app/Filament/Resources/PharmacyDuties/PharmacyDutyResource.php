<?php

namespace App\Filament\Resources\PharmacyDuties;

use App\Filament\Resources\PharmacyDuties\Pages\CreatePharmacyDuty;
use App\Filament\Resources\PharmacyDuties\Pages\EditPharmacyDuty;
use App\Filament\Resources\PharmacyDuties\Pages\ListPharmacyDuties;
use App\Filament\Resources\PharmacyDuties\Schemas\PharmacyDutyForm;
use App\Filament\Resources\PharmacyDuties\Tables\PharmacyDutiesTable;
use App\Models\PharmacyDuty;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PharmacyDutyResource extends Resource
{
    protected static ?string $model = PharmacyDuty::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static ?string $recordTitleAttribute = 'date';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('Contenu');
    }

    public static function getModelLabel(): string
    {
        return __('garde de pharmacie');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pharmacies de garde');
    }

    public static function form(Schema $schema): Schema
    {
        return PharmacyDutyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PharmacyDutiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPharmacyDuties::route('/'),
            'create' => CreatePharmacyDuty::route('/create'),
            'edit' => EditPharmacyDuty::route('/{record}/edit'),
        ];
    }
}
