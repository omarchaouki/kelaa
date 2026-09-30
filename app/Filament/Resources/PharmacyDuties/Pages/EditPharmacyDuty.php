<?php

namespace App\Filament\Resources\PharmacyDuties\Pages;

use App\Filament\Resources\PharmacyDuties\PharmacyDutyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPharmacyDuty extends EditRecord
{
    protected static string $resource = PharmacyDutyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
