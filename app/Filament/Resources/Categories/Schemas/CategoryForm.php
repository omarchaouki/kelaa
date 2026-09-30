<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Support\TranslatableFields;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TranslatableFields::make('name', __('Nom'))->columnSpanFull(),
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('slug')
                            ->label(__('Slug (URL)'))
                            ->required()
                            ->maxLength(100)
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (string $state) => Str::slug($state)),
                        // Two levels only: a parent must be a root category, and a
                        // category that already has children cannot get a parent.
                        Select::make('parent_id')
                            ->label(__('Catégorie parente'))
                            ->relationship(
                                'parent',
                                'slug',
                                fn (Builder $query, ?Category $record) => $query
                                    ->whereNull('parent_id')
                                    ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey())),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->name)
                            ->disabled(fn (?Category $record) => $record?->children()->exists() ?? false)
                            ->helperText(__('Laisser vide pour une catégorie principale.'))
                            ->searchable()
                            ->preload(),
                        TextInput::make('icon')
                            ->label(__('Icône (nom Lucide)'))
                            ->maxLength(50),
                        TextInput::make('schema_type')
                            ->label(__('Type schema.org'))
                            ->placeholder('LocalBusiness')
                            ->maxLength(50),
                        Toggle::make('is_emergency')->label(__('Urgence')),
                        Toggle::make('is_active')->label(__('Active'))->default(true),
                    ]),
            ]);
    }
}
