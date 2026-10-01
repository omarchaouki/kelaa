<?php

namespace App\Filament\Resources\SponsoredPlacements;

use App\Enums\PlacementStatus;
use App\Filament\Resources\SponsoredPlacements\Pages\ListSponsoredPlacements;
use App\Models\SponsoredPlacement;
use App\Support\Billing\BillingGate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sold placements. They are created by owners' orders and activated by
 * payment validation; admins can cancel one (e.g. after a refund).
 */
class SponsoredPlacementResource extends Resource
{
    protected static ?string $model = SponsoredPlacement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 42;

    public static function getNavigationGroup(): ?string
    {
        return __('Monétisation');
    }

    public static function getModelLabel(): string
    {
        return __('emplacement sponsorisé');
    }

    public static function getPluralModelLabel(): string
    {
        return __('emplacements sponsorisés');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['business', 'category', 'neighborhood', 'city']))
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('business.name')->label(__('Établissement'))->searchable(),
                TextColumn::make('category.name')->label(__('Catégorie')),
                TextColumn::make('neighborhood.name')->label(__('Quartier'))->placeholder(__('Toute la ville')),
                TextColumn::make('position')->label(__('Position')),
                TextColumn::make('starts_at')->label(__('Début'))->date('d/m/Y')->sortable(),
                TextColumn::make('ends_at')->label(__('Fin'))->date('d/m/Y'),
                TextColumn::make('status')->label(__('Statut'))->badge(),
                TextColumn::make('impressions')->label(__('Impressions'))->numeric(),
                TextColumn::make('clicks')->label(__('Clics'))->numeric(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Statut'))->options(PlacementStatus::options()),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label(__('Annuler'))
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (SponsoredPlacement $record) => in_array($record->status, [PlacementStatus::Pending, PlacementStatus::Active], true))
                    ->authorize(fn (SponsoredPlacement $record) => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (SponsoredPlacement $record): void {
                        BillingGate::asAdmin(auth()->user(), function () use ($record): void {
                            $record->forceFill(['status' => PlacementStatus::Cancelled])->save();
                            activity('billing')->performedOn($record)->causedBy(auth()->user())->log('placement.cancelled');
                        });
                        Notification::make()->success()->title(__('Emplacement annulé'))->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSponsoredPlacements::route('/'),
        ];
    }
}
