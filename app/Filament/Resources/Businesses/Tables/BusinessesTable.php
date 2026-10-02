<?php

namespace App\Filament\Resources\Businesses\Tables;

use App\Actions\Moderation\ModerateBusiness;
use App\Enums\BusinessStatus;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Business;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BusinessesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['city', 'neighborhood', 'categories']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('Nom'))->searchable()->limit(40),
                TextColumn::make('categories.name')->label(__('Catégories'))->badge()->limitList(2),
                TextColumn::make('neighborhood.name')->label(__('Quartier'))->placeholder('—'),
                TextColumn::make('status')->label(__('Statut'))->badge()->color(fn (BusinessStatus $state) => match ($state) {
                    BusinessStatus::Published => 'success',
                    BusinessStatus::Pending => 'warning',
                    BusinessStatus::Suspended => 'danger',
                    BusinessStatus::Rejected => 'gray',
                }),
                IconColumn::make('is_verified')->label(__('Vérifié'))->boolean(),
                TextColumn::make('possible_duplicate_ids')->label(__('Doublons possibles'))->badge()->color('danger')->placeholder('—')
                    ->formatStateUsing(fn ($state) => '#'.$state),
                TextColumn::make('rating_avg')->label(__('Note'))->numeric(1)->suffix(' ★'),
                TextColumn::make('rating_count')->label(__('Avis'))->numeric(),
                TextColumn::make('created_at')->label(__('Ajouté le'))->date('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Statut'))->options(BusinessStatus::options()),
                TernaryFilter::make('is_verified')->label(__('Vérifié')),
                SelectFilter::make('city_id')->label(__('Ville'))->relationship('city', 'slug'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('publish')
                    ->label(__('Publier'))
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Business $record) => $record->status !== BusinessStatus::Published)
                    ->authorize(fn (Business $record) => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (Business $record, ModerateBusiness $moderate): void {
                        $moderate->publish(auth()->user(), $record);
                        Notification::make()->success()->title(__('Fiche publiée'))->send();
                    }),
                Action::make('suspend')
                    ->label(__('Suspendre'))
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->visible(fn (Business $record) => $record->status !== BusinessStatus::Suspended)
                    ->authorize(fn (Business $record) => auth()->user()?->can('update', $record) ?? false)
                    ->schema([ReviewsTable::reasonField()])
                    ->action(function (Business $record, array $data, ModerateBusiness $moderate): void {
                        $moderate->suspend(auth()->user(), $record, $data['reason']);
                        Notification::make()->success()->title(__('Fiche suspendue'))->send();
                    }),
            ]);
    }
}
