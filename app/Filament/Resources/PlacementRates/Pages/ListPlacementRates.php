<?php

namespace App\Filament\Resources\PlacementRates\Pages;

use App\Filament\Resources\PlacementRates\PlacementRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlacementRates extends ListRecords
{
    protected static string $resource = PlacementRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
