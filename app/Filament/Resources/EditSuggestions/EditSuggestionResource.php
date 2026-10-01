<?php

namespace App\Filament\Resources\EditSuggestions;

use App\Enums\EditSuggestionStatus;
use App\Filament\Resources\EditSuggestions\Pages\ListEditSuggestions;
use App\Filament\Resources\EditSuggestions\Tables\EditSuggestionsTable;
use App\Models\EditSuggestion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EditSuggestionResource extends Resource
{
    protected static ?string $model = EditSuggestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('Modération');
    }

    public static function getModelLabel(): string
    {
        return __('suggestion de modification');
    }

    public static function getPluralModelLabel(): string
    {
        return __('suggestions de modification');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = EditSuggestion::query()->where('status', EditSuggestionStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function table(Table $table): Table
    {
        return EditSuggestionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEditSuggestions::route('/'),
        ];
    }
}
