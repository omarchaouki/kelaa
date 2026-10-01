<?php

namespace App\Actions\Owners;

use App\Actions\Reviews\SubmitReview;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\BusinessSpecialHour;
use App\Models\User;
use App\Services\Owners\ProfileCompleteness;
use App\Services\Plans\PlanFeatures;
use App\Support\Security\SafeImage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * What an owner changes directly from the dashboard. Fields that identify
 * the business (name, address, position, phone numbers, categories) are not
 * here: they go through SuggestEdit and a moderator.
 */
class UpdateBusinessProfile
{
    public function __construct(
        private readonly PlanFeatures $plans,
        private readonly ProfileCompleteness $completeness,
    ) {}

    /**
     * @param  array{description?: array<string, ?string>, email?: ?string, website?: ?string, social_links?: array<string, ?string>, price_range?: ?int, is_always_open?: bool}  $data
     *
     * @throws AuthorizationException
     */
    public function details(User $owner, Business $business, array $data): void
    {
        $this->authorize($owner, $business);

        if (isset($data['description'])) {
            $data['description'] = array_map(
                fn (?string $text) => $text === null ? null : mb_substr((string) SubmitReview::cleanBody($text), 0, 2000),
                array_intersect_key($data['description'], array_flip(['fr', 'ar', 'en'])),
            );
        }

        $allowed = array_intersect_key($data, array_flip(['description', 'email', 'website', 'social_links', 'price_range', 'is_always_open']));

        $business->fill($allowed);
        $business->forceFill(['last_activity_at' => now()])->save();

        $this->completeness->refresh($business);
        $this->log($owner, $business, 'business.owner_updated', array_keys($allowed));
    }

    /**
     * Replaces the weekly schedule.
     *
     * @param  array<int, list<array{0: string, 1: string}>>  $week  ISO day => [[opens, closes], ...]
     *
     * @throws AuthorizationException
     */
    public function hours(User $owner, Business $business, array $week): void
    {
        $this->authorize($owner, $business);

        DB::transaction(function () use ($business, $week): void {
            $business->hours()->delete();

            foreach ($week as $day => $slots) {
                if ($day < 1 || $day > 7) {
                    throw new InvalidArgumentException('Invalid day.');
                }

                foreach (array_slice($slots, 0, 4) as [$opens, $closes]) {
                    BusinessHour::query()->create(['business_id' => $business->id, 'day_of_week' => $day, 'opens_at' => $opens, 'closes_at' => $closes]);
                }
            }

            $business->forceFill(['last_activity_at' => now()])->save();
        });

        $business->unsetRelation('hours');
        $this->completeness->refresh($business);
        $this->log($owner, $business, 'business.hours_updated');
    }

    /**
     * @param  array{label: ?string, starts_on: string, ends_on: string, opens_at: ?string, closes_at: ?string, is_closed: bool}  $period
     *
     * @throws AuthorizationException
     */
    public function addSpecialPeriod(User $owner, Business $business, array $period): BusinessSpecialHour
    {
        $this->authorize($owner, $business);

        $special = BusinessSpecialHour::query()->create([
            'business_id' => $business->id,
            'label' => ['fr' => $period['label'] === null ? null : mb_substr(trim($period['label']), 0, 80)],
            'starts_on' => $period['starts_on'],
            'ends_on' => $period['ends_on'],
            'opens_at' => $period['is_closed'] ? null : $period['opens_at'],
            'closes_at' => $period['is_closed'] ? null : $period['closes_at'],
            'is_closed' => $period['is_closed'],
        ]);

        $this->log($owner, $business, 'business.special_hours_added');

        return $special;
    }

    /**
     * @throws AuthorizationException
     */
    public function removeSpecialPeriod(User $owner, Business $business, int $periodId): void
    {
        $this->authorize($owner, $business);

        // Scoped to this business: ids of other listings are ignored.
        $business->specialHours()->whereKey($periodId)->delete();
    }

    /**
     * @param  list<UploadedFile>  $photos
     *
     * @throws AuthorizationException
     */
    public function addPhotos(User $owner, Business $business, array $photos, string $collection = 'gallery'): int
    {
        $this->authorize($owner, $business);

        if (! in_array($collection, ['gallery', 'logo', 'cover'], true)) {
            throw new InvalidArgumentException('Unknown collection.');
        }

        if ($collection !== 'gallery' && ! $this->plans->enabled($business, 'logo_and_cover')) {
            throw new AuthorizationException(__('Le logo et la photo de couverture font partie des formules payantes.'));
        }

        $room = $collection === 'gallery'
            ? $this->plans->maxPhotos($business) - $business->getMedia('gallery')->count()
            : 1;

        $added = 0;

        foreach (array_slice($photos, 0, max(0, $room)) as $photo) {
            $business->addMedia(SafeImage::reencode($photo))
                ->usingFileName(bin2hex(random_bytes(12)).'.jpg')
                ->toMediaCollection($collection);
            $added++;
        }

        if ($added > 0) {
            $business->forceFill(['last_activity_at' => now()])->save();
            $business->unsetRelation('media');
            $this->completeness->refresh($business);
            $this->log($owner, $business, 'business.photos_added', ['count' => $added, 'collection' => $collection]);
        }

        return $added;
    }

    /**
     * @throws AuthorizationException
     */
    public function removePhoto(User $owner, Business $business, int $mediaId): void
    {
        $this->authorize($owner, $business);

        $business->media()->whereKey($mediaId)->whereIn('collection_name', ['gallery', 'logo', 'cover'])->get()->each->delete();
        $business->unsetRelation('media');
        $this->completeness->refresh($business);
    }

    /**
     * @throws AuthorizationException
     */
    private function authorize(User $owner, Business $business): void
    {
        if (! $owner->can('manage', $business)) {
            throw new AuthorizationException;
        }
    }

    /**
     * @param  array<int|string, mixed>  $properties
     */
    private function log(User $owner, Business $business, string $event, array $properties = []): void
    {
        activity('owners')->performedOn($business)->causedBy($owner)->withProperties($properties)->log($event);
    }
}
