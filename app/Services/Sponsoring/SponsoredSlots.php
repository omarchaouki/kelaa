<?php

namespace App\Services\Sponsoring;

use App\Enums\BusinessStatus;
use App\Enums\PlacementStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\SponsoredPlacement;
use App\Services\Search\BusinessSearch;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Picks the (at most 3) sponsored placements shown above a category listing.
 * Placements are sold per city + category, optionally narrowed to a
 * neighborhood; a neighborhood listing shows its own placements first,
 * then fills the remaining positions with city-wide ones.
 *
 * Selection is fixed by position today. When demand exceeds supply, a
 * rotating strategy can replace pick() without touching callers.
 */
class SponsoredSlots
{
    public const MAX_SLOTS = 3;

    /**
     * @return Collection<int, SponsoredPlacement>
     */
    public function for(City $city, Category $category, ?Neighborhood $neighborhood = null, ?CarbonInterface $at = null): Collection
    {
        $at ??= now();

        $candidates = SponsoredPlacement::query()
            ->where('city_id', $city->id)
            ->where('category_id', $category->id)
            ->where('status', PlacementStatus::Active)
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>', $at)
            ->where(fn (Builder $q) => $neighborhood === null
                ? $q->whereNull('neighborhood_id')
                : $q->whereNull('neighborhood_id')->orWhere('neighborhood_id', $neighborhood->id))
            ->whereHas('business', fn (Builder $q) => $q->where('status', BusinessStatus::Published))
            ->with(['business' => fn ($q) => $q->with(BusinessSearch::CARD_RELATIONS)])
            ->get();

        return $this->pick($candidates);
    }

    /**
     * @param  Collection<int, SponsoredPlacement>  $candidates
     * @return Collection<int, SponsoredPlacement>
     */
    protected function pick(Collection $candidates): Collection
    {
        return $candidates
            // Targeted (neighborhood) placements first, then by position.
            ->sortBy(fn (SponsoredPlacement $p) => [$p->neighborhood_id === null ? 1 : 0, $p->position, $p->id])
            ->unique('business_id')
            ->take(self::MAX_SLOTS)
            ->values();
    }
}
