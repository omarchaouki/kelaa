<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Support\Locales;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = [];

        foreach (Locales::supported() as $locale) {
            $locales[$locale] = Locales::nativeName($locale);
        }

        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')->label(__('Nom'))->maxLength(255),
                        // The phone is the login identity: it only changes through OTP verification.
                        TextInput::make('phone')->label(__('Téléphone'))->disabled()->dehydrated(false),
                        TextInput::make('email')->label(__('E-mail'))->email()->unique(ignoreRecord: true),
                        Select::make('locale')->label(__('Langue'))->options($locales)->required(),
                    ]),
                Section::make(__('Rôles'))
                    ->columnSpanFull()
                    ->schema([
                        CheckboxList::make('roles')
                            ->hiddenLabel()
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => Role::from($record->name)->getLabel())
                            ->columns(4),
                    ]),
            ]);
    }
}
