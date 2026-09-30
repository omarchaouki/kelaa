<?php

namespace App\Filament\Resources\PharmacyDuties\Pages;

use App\Filament\Resources\PharmacyDuties\PharmacyDutyResource;
use App\Models\PharmacyDuty;
use App\Services\Pharmacies\PharmacyDutyImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListPharmacyDuties extends ListRecords
{
    protected static string $resource = PharmacyDutyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('import')
                ->label(__('Importer un CSV'))
                ->icon('heroicon-o-arrow-up-tray')
                ->visible(fn () => auth()->user()?->can('create', PharmacyDuty::class) ?? false)
                ->modalDescription(__('Colonnes : pharmacie (slug de la fiche), date (AAAA-MM-JJ), debut (HH:MM), fin (HH:MM). Une fin avant le début se termine le lendemain. Rien n’est importé si une ligne est invalide.'))
                ->schema([
                    FileUpload::make('file')
                        ->label(__('Fichier CSV'))
                        ->disk('local')
                        ->directory('imports/pharmacies')
                        ->visibility('private')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->maxSize(PharmacyDutyImporter::MAX_BYTES / 1024)
                        ->required(),
                ])
                ->action(function (array $data, PharmacyDutyImporter $importer): void {
                    $path = Storage::disk('local')->path($data['file']);

                    try {
                        $result = $importer->import($path, config('directory.timezone'));
                    } finally {
                        // Uploaded files are never kept.
                        Storage::disk('local')->delete($data['file']);
                    }

                    activity()->causedBy(auth()->user())
                        ->withProperties(['imported' => $result['imported'], 'errors' => count($result['errors'])])
                        ->log('pharmacy_duties.imported');

                    if ($result['errors'] !== []) {
                        Notification::make()->danger()
                            ->title(__('Import refusé'))
                            ->body(implode("\n", $result['errors']))
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()->success()
                        ->title(__(':count gardes importées', ['count' => $result['imported']]))
                        ->send();
                }),
        ];
    }
}
