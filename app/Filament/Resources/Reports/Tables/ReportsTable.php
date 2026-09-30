<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Actions\Moderation\HandleReport;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Business;
use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewReply;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['reportable', 'reporter']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('Date'))->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('reportable_type')->label(__('Contenu'))->badge()->formatStateUsing(fn (string $state) => match ($state) {
                    'business' => __('Fiche'),
                    'review' => __('Avis'),
                    'review_reply' => __('Réponse'),
                    'media' => __('Photo'),
                    default => $state,
                }),
                TextColumn::make('excerpt')->label(__('Extrait'))->state(fn (Report $record) => self::excerpt($record))->wrap()->limit(80),
                TextColumn::make('reason')->label(__('Motif'))->badge(),
                TextColumn::make('details')->label(__('Détails'))->limit(60)->wrap()->placeholder('—'),
                TextColumn::make('status')->label(__('Statut'))->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Statut'))->options(ReportStatus::options())->default(ReportStatus::Open->value),
                SelectFilter::make('reason')->label(__('Motif'))->options(ReportReason::options()),
            ])
            ->recordActions([
                Action::make('uphold')
                    ->label(__('Retirer le contenu'))
                    ->color('danger')
                    ->icon('heroicon-o-shield-exclamation')
                    ->visible(fn (Report $record) => $record->status === ReportStatus::Open && $record->reportable !== null)
                    ->authorize(fn (Report $record) => auth()->user()?->can('update', $record) ?? false)
                    ->modalDescription(__('Avis et réponses sont masqués, photos supprimées, fiches suspendues. L’auteur reçoit le motif.'))
                    ->schema([self::noteField()])
                    ->action(function (Report $record, array $data, HandleReport $handle): void {
                        $handle->uphold(auth()->user(), $record, $data['note']);
                        Notification::make()->success()->title(__('Contenu retiré'))->send();
                    }),
                Action::make('dismiss')
                    ->label(__('Classer sans suite'))
                    ->color('gray')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (Report $record) => $record->status === ReportStatus::Open)
                    ->authorize(fn (Report $record) => auth()->user()?->can('update', $record) ?? false)
                    ->schema([self::noteField()])
                    ->action(fn (Report $record, array $data, HandleReport $handle) => $handle->dismiss(auth()->user(), $record, $data['note'])),
            ]);
    }

    private static function noteField(): Textarea
    {
        return Textarea::make('note')->label(__('Note / motif'))->required()->minLength(5)->maxLength(255);
    }

    private static function excerpt(Report $report): string
    {
        $target = $report->reportable;

        return match (true) {
            $target instanceof Business => $target->name,
            $target instanceof Review => '★'.$target->rating.' '.($target->body ?? ''),
            $target instanceof ReviewReply => $target->body,
            $target instanceof Media => $target->file_name,
            default => __('Contenu supprimé'),
        };
    }
}
