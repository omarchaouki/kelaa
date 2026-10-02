<?php

namespace App\Filament\Resources\BusinessClaims\Tables;

use App\Actions\Moderation\ModerateClaim;
use App\Enums\ClaimStatus;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\Plan;
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
                ->orderByDesc(Business::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('businesses.id', 'business_claims.business_id')
                    ->where('plan_expires_at', '>', now())
                    ->whereIn('plan_id', self::priorityPlanIds()))
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

    /**
     * Plans whose features include priority support (read in PHP: the JSON
     * layout differs between databases, so no LIKE on the column).
     *
     * @return list<int>
     */
    private static function priorityPlanIds(): array
    {
        $ids = Plan::query()->get(['id', 'features'])
            ->filter(fn (Plan $plan): bool => (bool) $plan->feature('priority_support'))
            ->modelKeys();

        return $ids === [] ? [0] : array_values($ids);
    }
}
