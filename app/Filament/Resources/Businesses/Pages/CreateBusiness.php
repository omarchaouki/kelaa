<?php

namespace App\Filament\Resources\Businesses\Pages;

use App\Enums\BusinessStatus;
use App\Filament\Resources\Businesses\BusinessResource;
use App\Filament\Resources\Businesses\Schemas\BusinessForm;
use App\Models\Business;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Listings created by the team are published right away.
 */
class CreateBusiness extends CreateRecord
{
    protected static string $resource = BusinessResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $business = new Business($data);
        $business->forceFill([
            'status' => BusinessStatus::Published,
            'published_at' => now(),
            'submitted_by' => auth()->id(),
        ])->save();

        return $business;
    }

    protected function afterCreate(): void
    {
        /** @var Business $business */
        $business = $this->getRecord();
        $state = $this->form->getRawState();

        BusinessForm::syncCategories($business, (array) ($state['category_ids'] ?? []), (int) $state['primary_category_id']);

        activity('moderation')->performedOn($business)->causedBy(auth()->user())->log('business.created');
    }
}
