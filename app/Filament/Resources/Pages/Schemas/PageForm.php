<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Support\TranslatableFields;
use App\Support\Locales;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        $bodyTabs = [];

        foreach (Locales::supported() as $locale) {
            $bodyTabs[] = Tab::make(Locales::nativeName($locale))->schema([
                RichEditor::make("body.{$locale}")
                    ->hiddenLabel()
                    ->required($locale === Locales::default())
                    ->formatStateUsing(fn (?Model $record) => $record?->getTranslation('body', $locale, false))
                    ->extraInputAttributes(['dir' => Locales::direction($locale)]),
            ]);
        }

        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('slug')
                            ->label(__('Slug (URL)'))
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (string $state) => Str::slug($state)),
                        Toggle::make('is_published')->label(__('Publiée'))->default(true),
                    ]),
                TranslatableFields::make('title', __('Titre'))->columnSpanFull(),
                TranslatableFields::make('meta_description', __('Meta description (SEO)'), fn (string $path) => Textarea::make($path)->rows(2)->maxLength(160), required: false)
                    ->columnSpanFull(),
                Tabs::make(__('Contenu'))->tabs($bodyTabs)->columnSpanFull(),
            ]);
    }
}
