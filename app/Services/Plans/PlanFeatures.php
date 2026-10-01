<?php

namespace App\Services\Plans;

use App\Models\Business;
use App\Models\Plan;
use Illuminate\Support\Facades\Cache;

/**
 * What a business may use right now: its paid plan while it is active,
 * otherwise the default (free) plan. Features never touch ranking or reviews.
 */
class PlanFeatures
{
    public function activePlan(Business $business): ?Plan
    {
        if ($business->plan_id !== null && $business->plan_expires_at?->isFuture()) {
            $business->loadMissing('plan');

            if ($business->plan?->is_active) {
                return $business->plan;
            }
        }

        return $this->defaultPlan();
    }

    public function get(Business $business, string $feature): mixed
    {
        $plan = $this->activePlan($business);

        return $plan?->feature($feature, config("owners.fallback_features.{$feature}"))
            ?? config("owners.fallback_features.{$feature}");
    }

    public function enabled(Business $business, string $feature): bool
    {
        return (bool) $this->get($business, $feature);
    }

    public function maxPhotos(Business $business): int
    {
        return max(0, (int) $this->get($business, 'max_photos'));
    }

    public function hasPaidPlan(Business $business): bool
    {
        $plan = $this->activePlan($business);

        return $plan !== null && ! $plan->isFree();
    }

    /**
     * Only the id is cached: the cache never stores objects
     * (cache.serializable_classes = false blocks object injection).
     */
    private function defaultPlan(): ?Plan
    {
        return once(function (): ?Plan {
            $id = Cache::remember('plans:default-id', now()->addMinutes(10), fn () => Plan::query()
                ->where('is_default', true)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->value('id'));

            return $id === null ? null : Plan::query()->find($id);
        });
    }
}
