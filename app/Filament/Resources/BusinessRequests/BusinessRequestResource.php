<?php

namespace App\Filament\Resources\BusinessRequests;

use App\Enums\BusinessStatus;
use App\Filament\Resources\BusinessRequests\Pages\ListBusinessRequests;
use App\Filament\Resources\BusinessRequests\Tables\BusinessRequestsTable;
use App\Models\Business;
use App\Support\PriceRange;
use App\Support\Security\SafeUrl;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * Listings submitted by members ("ajouter mon établissement"), waiting for a
 * moderator to publish or decline them. Same model as the Businesses
 * resource, limited to submissions.
 */
class BusinessRequestResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static ?string $slug = 'business-requests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 0;

    public static function getNavigationGroup(): ?string
    {
        return __('Modération');
    }

    public static function getNavigationLabel(): string
    {
        return __('Demandes d’ajout');
    }

    public static function getModelLabel(): string
    {
        return __('demande d’ajout');
    }

    public static function getPluralModelLabel(): string
    {
        return __('demandes d’ajout');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Business::query()->where('status', BusinessStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Submissions only: pending or declined, never published listings.
     *
     * @return Builder<Business>
     */
    public static function getEloquentQuery(): Builder
    {
        return Business::query()->whereIn('status', [BusinessStatus::Pending, BusinessStatus::Rejected]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Activité'))->columns(2)->columnSpanFull()->schema([
                TextEntry::make('name')->label(__('Nom')),
                TextEntry::make('categories.name')->label(__('Catégories'))->badge(),
                TextEntry::make('description')->label(__('Description'))->placeholder('—')->columnSpanFull(),
                TextEntry::make('price_range')->label(__('Prix'))->formatStateUsing(fn ($state) => PriceRange::label($state))->placeholder('—'),
                TextEntry::make('is_always_open')->label(__('Ouvert 24h/24'))->formatStateUsing(fn ($state) => $state ? __('Oui') : __('Non')),
            ]),
            Section::make(__('Contact et adresse'))->columns(2)->columnSpanFull()->schema([
                TextEntry::make('phone')->label(__('Téléphone'))->placeholder('—'),
                TextEntry::make('whatsapp')->label('WhatsApp')->placeholder('—'),
                TextEntry::make('website')->label(__('Site web'))->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => SafeUrl::normalize($state) ?? '—'),
                TextEntry::make('neighborhood.name')->label(__('Quartier'))->placeholder('—'),
                TextEntry::make('address')->label(__('Adresse'))->placeholder('—'),
                // Opens the exact pin on a map in a new tab.
                TextEntry::make('position')->label(__('Position'))
                    ->state(fn (Business $record) => new HtmlString('<a class="underline" target="_blank" rel="noopener noreferrer" href="'.e('https://www.google.com/maps/search/?api=1&query='.$record->latitude.','.$record->longitude).'">'.e($record->latitude.', '.$record->longitude).'</a>')),
            ]),
            Section::make(__('Demande'))->columns(2)->columnSpanFull()->schema([
                TextEntry::make('submitter.name')->label(__('Proposé par'))->placeholder('—'),
                TextEntry::make('submitter.phone')->label(__('Téléphone du demandeur'))->placeholder('—'),
                TextEntry::make('created_at')->label(__('Reçu le'))->dateTime('d/m/Y H:i'),
                TextEntry::make('status')->label(__('Statut'))->badge(),
                TextEntry::make('possible_duplicate_ids')->label(__('Doublons possibles'))->badge()->color('danger')->placeholder(__('Aucun'))
                    ->formatStateUsing(fn ($state) => '#'.$state),
                TextEntry::make('rejection_reason')->label(__('Motif du refus'))->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return BusinessRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessRequests::route('/'),
        ];
    }
}
