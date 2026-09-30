<?php

namespace App\Filament\Resources\ReviewReplies\Tables;

use App\Actions\Moderation\ModerateReply;
use App\Enums\ReplyStatus;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\ReviewReply;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewRepliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['review.business', 'user']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('Date'))->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('review.business.name')->label(__('Établissement'))->limit(30),
                TextColumn::make('review.body')->label(__('Avis'))->limit(40)->placeholder('—'),
                TextColumn::make('body')->label(__('Réponse'))->limit(60)->wrap(),
                TextColumn::make('status')->label(__('Statut'))->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Statut'))->options(ReplyStatus::options())->default(ReplyStatus::Pending->value),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('Publier'))
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (ReviewReply $record) => $record->status !== ReplyStatus::Published)
                    ->authorize(fn (ReviewReply $record) => auth()->user()?->can('moderate', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (ReviewReply $record, ModerateReply $moderate): void {
                        $moderate->approve(auth()->user(), $record);
                        Notification::make()->success()->title(__('Réponse publiée'))->send();
                    }),
                Action::make('reject')
                    ->label(__('Refuser'))
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (ReviewReply $record) => $record->status === ReplyStatus::Pending)
                    ->authorize(fn (ReviewReply $record) => auth()->user()?->can('moderate', $record) ?? false)
                    ->schema([ReviewsTable::reasonField()])
                    ->action(fn (ReviewReply $record, array $data, ModerateReply $moderate) => $moderate->reject(auth()->user(), $record, $data['reason'])),
                Action::make('hide')
                    ->label(__('Masquer'))
                    ->color('danger')
                    ->icon('heroicon-o-eye-slash')
                    ->visible(fn (ReviewReply $record) => $record->status === ReplyStatus::Published)
                    ->authorize(fn (ReviewReply $record) => auth()->user()?->can('moderate', $record) ?? false)
                    ->schema([ReviewsTable::reasonField()])
                    ->action(fn (ReviewReply $record, array $data, ModerateReply $moderate) => $moderate->hide(auth()->user(), $record, $data['reason'])),
            ]);
    }
}
