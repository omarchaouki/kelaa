<?php

namespace App\Filament\Resources\PharmacyDuties\Schemas;

use App\Enums\BusinessStatus;
use App\Services\Pharmacies\PharmacyDutyImporter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PharmacyDutyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('business_id')
                    ->label(__('Pharmacie'))
                    ->relationship(
                        'business',
                        'name',
                        fn (Builder $query) => $query->where('status', BusinessStatus::Published)
                            ->whereHas('categories', fn (Builder $q) => $q->where('slug', PharmacyDutyImporter::PHARMACY_CATEGORY)),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('date')
                    ->label(__('Date de garde'))
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->label(__('Début'))
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->label(__('Fin'))
                    ->seconds(false)
                    ->after('starts_at')
                    ->required(),
            ]);
    }
}
