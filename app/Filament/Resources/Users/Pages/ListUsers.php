<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Accounts are created by phone login only.
 */
class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
