<?php

namespace App\Filament\Resources\EditSuggestions\Tables;

use App\Actions\Moderation\ModerateEditSuggestion;
use App\Enums\EditSuggestionStatus;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\EditSuggestion;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EditSuggestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['business', 'user']))
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('created_at')->label(__('Date'))->dateTime('d/m/Y H:i'),
                TextColumn::make('business.name')->label(__('Établissement'))->searchable(),
                IconColumn::make('from_owner')->label(__('Propriétaire'))->boolean(),
                TextColumn::make('diff')
                    ->label(__('Changements'))
                    ->wrap()
                    ->state(fn (EditSuggestion $record) => self::diff($record)),
                TextColumn::make('comment')->label(__('Commentaire'))->limit(60)->placeholder('—'),
                TextColumn::make('status')->label(__('Statut'))->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Statut'))->options(EditSuggestionStatus::options())->default(EditSuggestionStatus::Pending->value),
            ])
            ->recordActions([
                Action::make('apply')
                    ->label(__('Appliquer'))
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (EditSuggestion $record) => $record->status === EditSuggestionStatus::Pending)
                    ->authorize(fn (EditSuggestion $record) => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalDescription(fn (EditSuggestion $record) => self::diff($record))
                    ->action(function (EditSuggestion $record, ModerateEditSuggestion $moderate): void {
                        $moderate->apply(auth()->user(), $record);
                        Notification::make()->success()->title(__('Fiche mise à jour'))->send();
                    }),
                Action::make('reject')
                    ->label(__('Refuser'))
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (EditSuggestion $record) => $record->status === EditSuggestionStatus::Pending)
                    ->authorize(fn (EditSuggestion $record) => auth()->user()?->can('update', $record) ?? false)
                    ->schema([ReviewsTable::reasonField()])
                    ->action(fn (EditSuggestion $record, array $data, ModerateEditSuggestion $moderate) => $moderate->reject(auth()->user(), $record, $data['reason'])),
            ]);
    }

    private static function diff(EditSuggestion $suggestion): string
    {
        $lines = [];

        foreach ($suggestion->changes as $field => $value) {
            $before = $field === 'permanently_closed' ? '' : (string) $suggestion->business?->getAttribute($field);
            $after = is_bool($value) ? ($value ? __('oui') : __('non')) : (string) $value;
            $lines[] = "{$field} : {$before} → {$after}";
        }

        return implode(' | ', $lines);
    }
}
