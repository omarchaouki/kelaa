<?php

namespace App\Filament\Resources\BannedWords;

use App\Filament\Resources\BannedWords\Pages\CreateBannedWord;
use App\Filament\Resources\BannedWords\Pages\EditBannedWord;
use App\Filament\Resources\BannedWords\Pages\ListBannedWords;
use App\Filament\Resources\BannedWords\Schemas\BannedWordForm;
use App\Filament\Resources\BannedWords\Tables\BannedWordsTable;
use App\Models\BannedWord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BannedWordResource extends Resource
{
    protected static ?string $model = BannedWord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?string $recordTitleAttribute = 'word';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('Modération');
    }

    public static function getModelLabel(): string
    {
        return __('mot interdit');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mots interdits');
    }

    public static function form(Schema $schema): Schema
    {
        return BannedWordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BannedWordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBannedWords::route('/'),
            'create' => CreateBannedWord::route('/create'),
            'edit' => EditBannedWord::route('/{record}/edit'),
        ];
    }
}
