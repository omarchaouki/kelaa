<?php

namespace App\Filament\Resources\Reviews;

use App\Enums\ReviewStatus;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Moderation queue for reviews. No create, edit or delete: moderators
 * approve, reject or hide, always with a reason for the latter two.
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Modération');
    }

    public static function getModelLabel(): string
    {
        return __('avis');
    }

    public static function getPluralModelLabel(): string
    {
        return __('avis');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Review::query()->where('status', ReviewStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->columnSpanFull()->schema([
                TextEntry::make('business.name')->label(__('Établissement')),
                TextEntry::make('user.phone')->label(__('Auteur'))->placeholder(__('Utilisateur supprimé')),
                TextEntry::make('status')->label(__('Statut'))->badge(),
                TextEntry::make('rating')->label(__('Note'))->suffix(' / 5'),
                TextEntry::make('created_at')->label(__('Créé le'))->dateTime(),
                TextEntry::make('edited_at')->label(__('Modifié le'))->dateTime()->placeholder('—'),
                TextEntry::make('flags')->label(__('Alertes'))->badge()->color('danger')->placeholder(__('Aucune'))->columnSpanFull(),
                TextEntry::make('body')->label(__('Texte'))->placeholder('—')->columnSpanFull(),
                TextEntry::make('moderation_reason')->label(__('Motif de modération'))->placeholder('—')->columnSpanFull(),
            ]),
            ImageEntry::make('photo_urls')
                ->label(__('Photos'))
                ->state(fn (Review $record) => $record->getMedia('photos')->map(fn ($media) => $media->getUrl('thumb'))->all())
                ->placeholder(__('Aucune'))
                ->columnSpanFull(),
            RepeatableEntry::make('revisions')
                ->label(__('Versions précédentes'))
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('created_at')->label(__('Remplacée le'))->dateTime(),
                    TextEntry::make('rating')->label(__('Note')),
                    TextEntry::make('body')->label(__('Texte'))->placeholder('—'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
        ];
    }
}
