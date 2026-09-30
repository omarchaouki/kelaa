<?php

namespace App\Filament\Resources\BannedWords\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BannedWordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('word')
                    ->label(__('Mot ou expression'))
                    ->helperText(__('Majuscules, accents et voyelles arabes sont ignorés. Un avis qui le contient part en modération.'))
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
            ]);
    }
}
