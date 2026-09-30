<?php

namespace App\Filament\Resources\BannedWords\Pages;

use App\Filament\Resources\BannedWords\BannedWordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBannedWord extends CreateRecord
{
    protected static string $resource = BannedWordResource::class;
}
