<?php

namespace App\Filament\Resources\BusinessRequests\Tables;

use App\Actions\Moderation\ModerateBusiness;
use App\Enums\BusinessStatus;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Business;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BusinessRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['categories', 'neighborhood', 'submitter']))
            // Oldest request first: first come, first served.
            ->defaultSort('created_at')
            ->emptyStateHeading(__('Aucune demande en attente'))
            ->emptyStateDescription(__('Les établissements proposés par les habitants apparaîtront ici.'))
            ->columns([
                TextColumn::make('created_at')->label(__('Reçu le'))->since()->sortable(),
                TextColumn::make('name')->label(__('Nom'))->searchable()->weight('bold'),
                TextColumn::make('categories.name')->label(__('Catégories'))->badge()->limitList(2),
                TextColumn::make('neighborhood.name')->label(__('Quartier'))->placeholder('—'),
                TextColumn::make('submitter.phone')->label(__('Proposé par'))->placeholder('—'),
                TextColumn::make('possible_duplicate_ids')->label(__('Doublons'))->badge()->color('danger')->placeholder('—')
                    ->formatStateUsing(fn ($state) => '#'.$state),
                TextColumn::make('status')->label(__('Statut'))->badge()->color(fn (BusinessStatus $state) => $state === BusinessStatus::Pending ? 'warning' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Statut'))
                    ->options([BusinessStatus::Pending->value => BusinessStatus::Pending->getLabel(), BusinessStatus::Rejected->value => BusinessStatus::Rejected->getLabel()])
                    ->default(BusinessStatus::Pending->value),
            ])
            ->recordActions([
                ViewAction::make()->label(__('Détails')),
                Action::make('approve')
                    ->label(__('Valider'))
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->button()
                    ->visible(fn (Business $record) => $record->status === BusinessStatus::Pending)
                    ->authorize(fn (Business $record) => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Business $record) => __('Publier « :name » ?', ['name' => $record->name]))
                    ->modalDescription(__('La fiche devient visible par tous et la personne qui l’a proposée est prévenue.'))
                    ->action(function (Business $record, ModerateBusiness $moderate): void {
                        $moderate->publish(auth()->user(), $record);
                        Notification::make()->success()->title(__('Fiche publiée'))->send();
                    }),
                Action::make('decline')
                    ->label(__('Refuser'))
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->button()
                    ->visible(fn (Business $record) => $record->status === BusinessStatus::Pending)
                    ->authorize(fn (Business $record) => auth()->user()?->can('update', $record) ?? false)
                    ->schema([ReviewsTable::reasonField()])
                    ->modalDescription(__('Le motif est envoyé à la personne qui a proposé la fiche.'))
                    ->action(function (Business $record, array $data, ModerateBusiness $moderate): void {
                        $moderate->decline(auth()->user(), $record, $data['reason']);
                        Notification::make()->success()->title(__('Demande refusée'))->send();
                    }),
            ]);
    }
}
