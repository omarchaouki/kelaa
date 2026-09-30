<?php

namespace App\Filament\Resources\BannedWords\Pages;

use App\Filament\Resources\BannedWords\BannedWordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBannedWords extends ListRecords
{
    protected static string $resource = BannedWordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
