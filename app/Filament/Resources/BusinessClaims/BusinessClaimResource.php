<?php

namespace App\Filament\Resources\BusinessClaims;

use App\Enums\ClaimStatus;
use App\Filament\Resources\BusinessClaims\Pages\ListBusinessClaims;
use App\Filament\Resources\BusinessClaims\Tables\BusinessClaimsTable;
use App\Models\BusinessClaim;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class BusinessClaimResource extends Resource
{
    protected static ?string $model = BusinessClaim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('Modération');
    }

    public static function getModelLabel(): string
    {
        return __('revendication');
    }

    public static function getPluralModelLabel(): string
    {
        return __('revendications');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = BusinessClaim::query()->where('status', ClaimStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextEntry::make('business.name')->label(__('Établissement')),
                TextEntry::make('business.phone')->label(__('Téléphone de la fiche'))->placeholder('—'),
                TextEntry::make('user.name')->label(__('Demandeur'))->placeholder('—'),
                TextEntry::make('user.phone')->label(__('Téléphone du demandeur')),
                TextEntry::make('method')->label(__('Méthode'))->badge(),
                TextEntry::make('status')->label(__('Statut'))->badge(),
                TextEntry::make('notes')->label(__('Message'))->placeholder('—')->columnSpanFull(),
                // Private documents: links to the staff-only, sandboxed download route.
                TextEntry::make('documents')
                    ->label(__('Justificatifs'))
                    ->columnSpanFull()
                    ->state(fn (BusinessClaim $record) => new HtmlString($record->getMedia('documents')
                        ->map(fn ($media, $i) => '<a class="underline" target="_blank" rel="noopener noreferrer" href="'.e(route('admin.private-media', ['media' => $media->id])).'">'
                            .e(__('Document :n', ['n' => $i + 1])).' ('.e(str_starts_with((string) $media->mime_type, 'image/') ? 'image' : 'PDF').')</a>')
                        ->implode(' · ') ?: '—')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return BusinessClaimsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessClaims::route('/'),
        ];
    }
}
