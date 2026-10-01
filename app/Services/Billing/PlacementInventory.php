<?php

namespace App\Services\Billing;

use App\Enums\PaymentStatus;
use App\Enums\PlacementStatus;
use App\Enums\PlacementUnit;
use App\Models\SponsoredPlacement;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Limited inventory: one business per (city, category, neighborhood|city-wide,
 * position) at any moment. Unpaid orders hold their slot until their payment
 * expires, so two owners can never pay for the same slot.
 */
class PlacementInventory
{
    /**
     * Booking periods start at the beginning of a day and run for whole weeks or months.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function period(CarbonInterface $start, PlacementUnit $unit, int $units): array
    {
        $starts = CarbonImmutable::instance($start)->startOfDay();
        $ends = $unit === PlacementUnit::Month ? $starts->addMonthsNoOverflow($units) : $starts->addWeeks($units);

        return [$starts, $ends];
    }

    public function isAvailable(int $cityId, int $categoryId, ?int $neighborhoodId, int $position, CarbonInterface $starts, CarbonInterface $ends, ?int $ignoreId = null): bool
    {
        return ! $this->occupying($cityId, $categoryId, $neighborhoodId, $position, $starts, $ends)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * Placements holding the slot during [starts, ends): active ones, and
     * pending ones whose payment has not expired yet.
     *
     * @return Builder<SponsoredPlacement>
     */
    public function occupying(int $cityId, int $categoryId, ?int $neighborhoodId, int $position, CarbonInterface $starts, CarbonInterface $ends): Builder
    {
        return SponsoredPlacement::query()
            ->where('city_id', $cityId)
            ->where('category_id', $categoryId)
            ->where('position', $position)
            ->when($neighborhoodId === null, fn (Builder $q) => $q->whereNull('neighborhood_id'), fn (Builder $q) => $q->where('neighborhood_id', $neighborhoodId))
            ->where('starts_at', '<', $ends)
            ->where('ends_at', '>', $starts)
            ->where(fn (Builder $q) => $q
                ->where('status', PlacementStatus::Active)
                ->orWhere(fn (Builder $q) => $q
                    ->where('status', PlacementStatus::Pending)
                    ->whereHas('payment', fn (Builder $q) => $q
                        ->where('status', PaymentStatus::Pending)
                        ->where('expires_at', '>', now()))));
    }

    /**
     * Free/taken grid for the next weeks, for the owner form and the admin calendar.
     *
     * @return list<array{week: CarbonImmutable, positions: array<int, bool>}>
     */
    public function weeklyGrid(int $cityId, int $categoryId, ?int $neighborhoodId, int $weeks = 12): array
    {
        $grid = [];
        $monday = now()->toImmutable()->startOfWeek(CarbonInterface::MONDAY);

        for ($i = 0; $i < $weeks; $i++) {
            $start = $monday->addWeeks($i);
            $positions = [];

            foreach ([1, 2, 3] as $position) {
                $positions[$position] = $this->isAvailable($cityId, $categoryId, $neighborhoodId, $position, $start, $start->addWeek());
            }

            $grid[] = ['week' => $start, 'positions' => $positions];
        }

        return $grid;
    }
}
