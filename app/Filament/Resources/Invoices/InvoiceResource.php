<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\Invoice;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?int $navigationSort = 44;

    public static function getNavigationGroup(): ?string
    {
        return __('Monétisation');
    }

    public static function getModelLabel(): string
    {
        return __('facture');
    }

    public static function getPluralModelLabel(): string
    {
        return __('factures');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('issued_at', 'desc')
            ->columns([
                TextColumn::make('number')->label(__('Numéro'))->searchable(),
                TextColumn::make('issued_at')->label(__('Date'))->date('d/m/Y')->sortable(),
                TextColumn::make('client_name')->label(__('Client'))->searchable(),
                TextColumn::make('client_ice')->label('ICE')->placeholder('—'),
                TextColumn::make('total')->label(__('Total TTC'))->money('MAD'),
            ])
            ->recordActions([
                Action::make('download')
                    ->label(__('PDF'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Invoice $record) => route('admin.invoice', ['invoice' => $record->id])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
        ];
    }
}
