<?php

namespace App\Filament\Resources\BannedWords\Pages;

use App\Filament\Resources\BannedWords\BannedWordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBannedWord extends EditRecord
{
    protected static string $resource = BannedWordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
