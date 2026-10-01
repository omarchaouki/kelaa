<?php

namespace App\Filament\Resources\BusinessClaims\Tables;

use App\Actions\Moderation\ModerateClaim;
use App\Enums\ClaimStatus;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\BusinessClaim;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BusinessClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Paid plans with priority support are handled first (spec §8), then oldest first.
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['business.plan', 'user', 'media'])
                ->orderByRaw('exists (select 1 from businesses b join plans p on p.id = b.plan_id where b.id = business_claims.business_id and b.plan_expires_at > ? and p.features like ?) desc', [now(), '%"priority_support":true%'])
                ->orderBy('created_at'))
            ->columns([
                TextColumn::make('created_at')->label(__('Date'))->dateTime('d/m/Y H:i'),
                TextColumn::make('business.name')->label(__('Établissement'))->searchable(),
                TextColumn::make('user.phone')->label(__('Demandeur')),
                TextColumn::make('method')->label(__('Méthode'))->badge(),
                TextColumn::make('status')->label(__('Statut'))->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Statut'))->options(ClaimStatus::options())->default(ClaimStatus::Pending->value),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label(__('Approuver'))
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (BusinessClaim $record) => $record->status === ClaimStatus::Pending)
                    ->authorize(fn (BusinessClaim $record) => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalDescription(__('Le demandeur devient propriétaire de la fiche. Vérifiez les justificatifs.'))
                    ->action(function (BusinessClaim $record, ModerateClaim $moderate): void {
                        $moderate->approve(auth()->user(), $record);
                        Notification::make()->success()->title(__('Revendication approuvée'))->send();
                    }),
                Action::make('reject')
                    ->label(__('Refuser'))
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (BusinessClaim $record) => $record->status === ClaimStatus::Pending)
                    ->authorize(fn (BusinessClaim $record) => auth()->user()?->can('update', $record) ?? false)
                    ->schema([ReviewsTable::reasonField()])
                    ->action(fn (BusinessClaim $record, array $data, ModerateClaim $moderate) => $moderate->reject(auth()->user(), $record, $data['reason'])),
            ]);
    }
}
