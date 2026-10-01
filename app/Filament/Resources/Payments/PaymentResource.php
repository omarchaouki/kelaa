<?php

namespace App\Filament\Resources\Payments;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

/**
 * Payment validation (admins only). Validating activates the subscription or
 * placement automatically and issues the invoice.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?int $navigationSort = 41;

    public static function getNavigationGroup(): ?string
    {
        return __('Monétisation');
    }

    public static function getModelLabel(): string
    {
        return __('paiement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('paiements');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Payment::query()->where('status', PaymentStatus::Pending)->whereHas('media', fn ($q) => $q->where('collection_name', 'receipt'))->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->columnSpanFull()->schema([
                TextEntry::make('reference')->label(__('Référence'))->copyable(),
                TextEntry::make('amount')->label(__('Montant'))->money('MAD'),
                TextEntry::make('status')->label(__('Statut'))->badge(),
                TextEntry::make('business.name')->label(__('Établissement')),
                TextEntry::make('purpose')->label(__('Objet'))->badge(),
                TextEntry::make('method')->label(__('Moyen'))->badge(),
                TextEntry::make('user.phone')->label(__('Payeur')),
                TextEntry::make('created_at')->label(__('Commandé le'))->dateTime(),
                TextEntry::make('expires_at')->label(__('Expire le'))->dateTime(),
                TextEntry::make('rejection_reason')->label(__('Motif'))->placeholder('—')->columnSpanFull(),
                TextEntry::make('receipt')
                    ->label(__('Reçu'))
                    ->columnSpanFull()
                    ->state(function (Payment $record) {
                        $media = $record->getFirstMedia('receipt');

                        if ($media === null) {
                            return __('Aucun reçu');
                        }

                        $url = e(route('admin.private-media', ['media' => $media->id]));

                        return new HtmlString(str_starts_with((string) $media->mime_type, 'image/')
                            ? '<a href="'.$url.'" target="_blank" rel="noopener noreferrer"><img src="'.$url.'" alt="" style="max-height: 320px"></a>'
                            : '<a class="underline" href="'.$url.'" rel="noopener noreferrer">'.e(__('Télécharger le PDF')).'</a>');
                    }),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
        ];
    }
}
