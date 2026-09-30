<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A synonym group: "plombier" <=> ["سباك", "plombi"].
 */
#[Fillable(['term', 'synonyms', 'locale'])]
class SearchSynonym extends Model
{
    protected function casts(): array
    {
        return [
            'synonyms' => 'array',
        ];
    }
}
