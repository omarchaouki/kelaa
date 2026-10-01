<?php

namespace App\Filament\Resources\PlacementRates\Pages;

use App\Filament\Resources\PlacementRates\PlacementRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlacementRate extends EditRecord
{
    protected static string $resource = PlacementRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
