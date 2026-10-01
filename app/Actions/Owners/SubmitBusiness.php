<?php

namespace App\Actions\Owners;

use App\Actions\Reviews\SubmitReview;
use App\Enums\BusinessStatus;
use App\Exceptions\RateLimited;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\User;
use App\Services\Owners\DuplicateDetector;
use App\Support\Geo\Coordinates;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A member suggests a new listing. It stays "pending" until a moderator
 * publishes it, and possible duplicates are recorded for the moderator.
 */
class SubmitBusiness
{
    public function __construct(
        private readonly DuplicateDetector $duplicates,
    ) {}

    /**
     * @param  array{city_id: int, neighborhood_id: ?int, name: string, description: ?string, address: ?string, latitude: float, longitude: float, phone: ?string, whatsapp: ?string, website: ?string, price_range: ?int, is_always_open: bool}  $data
     * @param  list<int>  $categoryIds  First one is the primary category.
     * @param  array<int, list<array{0: string, 1: string}>>  $hours  ISO day => [[opens, closes], ...]
     *
     * @throws AuthorizationException
     * @throws RateLimited
     */
    public function __invoke(User $user, array $data, array $categoryIds, array $hours): Business
    {
        if ($user->isBanned() || ! $user->hasVerifiedPhone()) {
            throw new AuthorizationException(__('Un numéro de mobile vérifié est nécessaire.'));
        }

        RateLimited::guard('submit-business:'.$user->id, (int) config('owners.submissions_per_day'), 86400);

        $duplicates = $this->duplicates->find(
            $data['city_id'],
            $data['name'],
            new Coordinates($data['latitude'], $data['longitude']),
            $data['phone'],
            $data['whatsapp'],
        );

        return DB::transaction(function () use ($user, $data, $categoryIds, $hours, $duplicates): Business {
            $business = new Business([
                'city_id' => $data['city_id'],
                'neighborhood_id' => $data['neighborhood_id'],
                'name' => trim($data['name']),
                'slug' => $this->uniqueSlug($data['city_id'], $data['name']),
                'description' => ['fr' => SubmitReview::cleanBody($data['description'])],
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'phone' => $data['phone'],
                'whatsapp' => $data['whatsapp'],
                'website' => $data['website'],
                'price_range' => $data['price_range'],
                'is_always_open' => $data['is_always_open'],
            ]);

            $business->forceFill([
                'status' => BusinessStatus::Pending,
                'submitted_by' => $user->id,
                'possible_duplicate_ids' => $duplicates->isEmpty() ? null : $duplicates->pluck('id')->all(),
                'last_activity_at' => now(),
            ])->save();

            $primary = $categoryIds[0];
            $business->categories()->sync(collect(array_unique($categoryIds))
                ->mapWithKeys(fn (int $id) => [$id => ['is_primary' => $id === $primary]])
                ->all());

            foreach ($hours as $day => $slots) {
                foreach ($slots as [$opens, $closes]) {
                    BusinessHour::query()->create([
                        'business_id' => $business->id,
                        'day_of_week' => $day,
                        'opens_at' => $opens,
                        'closes_at' => $closes,
                    ]);
                }
            }

            activity('owners')->performedOn($business)->causedBy($user)
                ->withProperties(['possible_duplicates' => $business->possible_duplicate_ids])
                ->log('business.submitted');

            return $business;
        });
    }

    private function uniqueSlug(int $cityId, string $name): string
    {
        $base = Str::limit(Str::slug($name) ?: 'etablissement', 80, '');

        do {
            $slug = $base.'-'.Str::lower(Str::random(4));
        } while (Business::withTrashed()->where('city_id', $cityId)->where('slug', $slug)->exists());

        return $slug;
    }
}
