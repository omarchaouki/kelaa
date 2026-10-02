<?php

namespace App\Filament\Resources\BusinessRequests\Pages;

use App\Filament\Resources\BusinessRequests\BusinessRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListBusinessRequests extends ListRecords
{
    protected static string $resource = BusinessRequestResource::class;
}
