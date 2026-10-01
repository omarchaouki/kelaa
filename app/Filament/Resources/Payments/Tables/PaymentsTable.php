<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Actions\Billing\HandlePayment;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Exceptions\SlotUnavailable;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['business', 'user', 'media', 'invoice']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('Date'))->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('reference')->label(__('Référence'))->searchable()->copyable(),
                TextColumn::make('business.name')->label(__('Établissement'))->searchable()->limit(30),
                TextColumn::make('purpose')->label(__('Objet'))->badge(),
                TextColumn::make('amount')->label(__('Montant'))->money('MAD')->sortable(),
                TextColumn::make('method')->label(__('Moyen'))->badge(),
                IconColumn::make('has_receipt')->label(__('Reçu'))->boolean()
                    ->state(fn (Payment $record) => $record->getFirstMedia('receipt') !== null),
                TextColumn::make('status')->label(__('Statut'))->badge()->color(fn (PaymentStatus $state) => match ($state) {
                    PaymentStatus::Validated => 'success',
                    PaymentStatus::Pending => 'warning',
                    default => 'danger',
                }),
                TextColumn::make('invoice.number')->label(__('Facture'))->placeholder('—')
                    ->url(fn (Payment $record) => $record->invoice ? route('admin.invoice', ['invoice' => $record->invoice->id]) : null),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Statut'))->options(PaymentStatus::options())->default(PaymentStatus::Pending->value),
                SelectFilter::make('purpose')->label(__('Objet'))->options(PaymentPurpose::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('validate')
                    ->label(__('Valider'))
                    ->color('success')
                    ->icon('heroicon-o-check-badge')
                    ->visible(fn (Payment $record) => $record->status === PaymentStatus::Pending)
                    ->authorize(fn (Payment $record) => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalDescription(fn (Payment $record) => __('Confirmez avoir reçu :amount DH (référence :reference). Le service sera activé et la facture émise.', ['amount' => $record->amount, 'reference' => $record->reference]))
                    ->action(function (Payment $record, HandlePayment $payments): void {
                        try {
                            $payments->validate(auth()->user(), $record);
                        } catch (SlotUnavailable $e) {
                            Notification::make()->danger()->title($e->getMessage())->persistent()->send();

                            return;
                        }

                        Notification::make()->success()->title(__('Paiement validé, service activé'))->send();
                    }),
                Action::make('reject')
                    ->label(__('Refuser'))
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (Payment $record) => $record->status === PaymentStatus::Pending)
                    ->authorize(fn (Payment $record) => auth()->user()?->can('update', $record) ?? false)
                    ->schema([ReviewsTable::reasonField()])
                    ->action(fn (Payment $record, array $data, HandlePayment $payments) => $payments->reject(auth()->user(), $record, $data['reason'])),
                Action::make('refund')
                    ->label(__('Marquer remboursé'))
                    ->color('gray')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (Payment $record) => $record->status === PaymentStatus::Validated)
                    ->authorize(fn (Payment $record) => auth()->user()?->can('update', $record) ?? false)
                    ->modalDescription(__('Le remboursement se fait hors plateforme. Le service payé est arrêté.'))
                    ->schema([ReviewsTable::reasonField()])
                    ->action(fn (Payment $record, array $data, HandlePayment $payments) => $payments->refund(auth()->user(), $record, $data['reason'])),
            ]);
    }
}
