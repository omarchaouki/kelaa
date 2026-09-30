<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Actions\Moderation\ModerateReview;
use App\Enums\ReviewStatus;
use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['business', 'user', 'revisions', 'media']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('Date'))->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('business.name')->label(__('Établissement'))->searchable()->limit(30),
                TextColumn::make('rating')->label(__('Note'))->formatStateUsing(fn (int $state) => str_repeat('★', $state))->color('warning'),
                TextColumn::make('body')->label(__('Texte'))->limit(60)->wrap()->placeholder('—'),
                TextColumn::make('status')->label(__('Statut'))->badge()->color(fn (ReviewStatus $state) => match ($state) {
                    ReviewStatus::Published => 'success',
                    ReviewStatus::Pending => 'warning',
                    default => 'danger',
                }),
                TextColumn::make('flags')->label(__('Alertes'))->badge()->color('danger')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Statut'))->options(ReviewStatus::options())->default(ReviewStatus::Pending->value),
                TernaryFilter::make('flags')->label(__('Avec alertes'))->nullable(),
                SelectFilter::make('rating')->label(__('Note'))->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label(__('Publier'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Review $record) => in_array($record->status, [ReviewStatus::Pending, ReviewStatus::Rejected, ReviewStatus::HiddenByModeration], true))
                    ->authorize(fn (Review $record) => auth()->user()?->can('moderate', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (Review $record, ModerateReview $moderate): void {
                        $moderate->approve(auth()->user(), $record);
                        Notification::make()->success()->title(__('Avis publié'))->send();
                    }),
                Action::make('reject')
                    ->label(__('Refuser'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Review $record) => $record->status === ReviewStatus::Pending)
                    ->authorize(fn (Review $record) => auth()->user()?->can('moderate', $record) ?? false)
                    ->schema([self::reasonField()])
                    ->action(function (Review $record, array $data, ModerateReview $moderate): void {
                        $moderate->reject(auth()->user(), $record, $data['reason']);
                        Notification::make()->success()->title(__('Avis refusé, l’auteur est prévenu'))->send();
                    }),
                Action::make('hide')
                    ->label(__('Masquer'))
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->visible(fn (Review $record) => $record->status === ReviewStatus::Published)
                    ->authorize(fn (Review $record) => auth()->user()?->can('moderate', $record) ?? false)
                    ->schema([self::reasonField()])
                    ->action(function (Review $record, array $data, ModerateReview $moderate): void {
                        $moderate->hide(auth()->user(), $record, $data['reason']);
                        Notification::make()->success()->title(__('Avis masqué, l’auteur est prévenu'))->send();
                    }),
            ]);
    }

    public static function reasonField(): Textarea
    {
        return Textarea::make('reason')
            ->label(__('Motif (envoyé à l’auteur)'))
            ->required()
            ->minLength(5)
            ->maxLength(500);
    }
}
