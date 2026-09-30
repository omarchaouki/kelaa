<?php

namespace App\Models;

use App\Observers\SearchSynonymObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $term
 * @property list<string> $synonyms
 * @property string|null $locale
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * A synonym group: "plombier" <=> ["سباك", "plombi"].
 */
#[Fillable(['term', 'synonyms', 'locale'])]
#[ObservedBy(SearchSynonymObserver::class)]
class SearchSynonym extends Model
{
    protected function casts(): array
    {
        return [
            'synonyms' => 'array',
        ];
    }
}
