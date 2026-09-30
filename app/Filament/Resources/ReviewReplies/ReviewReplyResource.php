<?php

namespace App\Filament\Resources\ReviewReplies;

use App\Enums\ReplyStatus;
use App\Filament\Resources\ReviewReplies\Pages\ListReviewReplies;
use App\Filament\Resources\ReviewReplies\Tables\ReviewRepliesTable;
use App\Models\ReviewReply;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReviewReplyResource extends Resource
{
    protected static ?string $model = ReviewReply::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Modération');
    }

    public static function getModelLabel(): string
    {
        return __('réponse');
    }

    public static function getPluralModelLabel(): string
    {
        return __('réponses');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = ReviewReply::query()->where('status', ReplyStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function table(Table $table): Table
    {
        return ReviewRepliesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviewReplies::route('/'),
        ];
    }
}
