<?php

namespace App\Filament\Resources\Businesses\Pages;

use App\Filament\Resources\Businesses\BusinessResource;
use App\Filament\Resources\Businesses\Schemas\BusinessForm;
use App\Models\Business;
use Filament\Resources\Pages\EditRecord;

class EditBusiness extends EditRecord
{
    protected static string $resource = BusinessResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Business $business */
        $business = $this->getRecord();
        $business->loadMissing('categories');

        $data['category_ids'] = $business->categories->pluck('id')->all();
        $data['primary_category_id'] = $business->categories->firstWhere('pivot.is_primary', true)?->id;

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Business $business */
        $business = $this->getRecord();
        $state = $this->form->getRawState();

        BusinessForm::syncCategories($business, (array) ($state['category_ids'] ?? []), (int) $state['primary_category_id']);

        activity('moderation')->performedOn($business)->causedBy(auth()->user())->log('business.edited');
    }
}
