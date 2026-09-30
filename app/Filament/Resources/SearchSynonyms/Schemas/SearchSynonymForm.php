<?php

namespace App\Filament\Resources\SearchSynonyms\Schemas;

use App\Support\Locales;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SearchSynonymForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = [];

        foreach (Locales::supported() as $locale) {
            $locales[$locale] = Locales::nativeName($locale);
        }

        return $schema
            ->components([
                TextInput::make('term')
                    ->label(__('Terme'))
                    ->required()
                    ->maxLength(100),
                Select::make('locale')
                    ->label(__('Langue'))
                    ->options($locales)
                    ->placeholder(__('Toutes les langues')),
                TagsInput::make('synonyms')
                    ->label(__('Synonymes'))
                    ->helperText(__('Français, arabe et darija en lettres latines. Exemple : سباك, plombi.'))
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
