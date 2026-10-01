<?php

namespace App\Filament\Resources\EditSuggestions\Pages;

use App\Filament\Resources\EditSuggestions\EditSuggestionResource;
use Filament\Resources\Pages\ListRecords;

class ListEditSuggestions extends ListRecords
{
    protected static string $resource = EditSuggestionResource::class;
}
