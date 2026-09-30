<?php

namespace App\Services\Tracking;

use App\Enums\ContactEventSource;
use App\Enums\ContactEventType;
use App\Jobs\RecordContactEvent;
use App\Jobs\RecordPlacementImpressions;
use App\Models\Business;
use App\Models\SponsoredPlacement;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Records profile views, contact button clicks and sponsored impressions.
 * Writes are queued; the same visitor counts once per business/type per
 * window so reloading a page or mashing a button cannot inflate statistics
 * (which may later be billed per lead).
 */
class ContactEventRecorder
{
    public const DEDUPE_MINUTES = 30;

    public const NETWORK_DEDUPE_MINUTES = 2;

    public function record(Business $business, ContactEventType $type, ContactEventSource $source, Request $request, ?SponsoredPlacement $placement = null): bool
    {
        if (Visitor::isBot($request) || ! $business->isPublished()) {
            return false;
        }

        // The placement must really belong to this business and be running.
        if ($placement !== null && ($placement->business_id !== $business->id || ! $placement->isRunning())) {
            $placement = null;
            $source = $source === ContactEventSource::Sponsored ? ContactEventSource::Organic : $source;
        }

        $visitor = Visitor::dailyHash($request);
        $suffix = "{$business->id}:{$type->value}";

        // Clients that drop cookies get a new session each time: a short
        // per-network window stops rapid repeats without undercounting the
        // many real users who share a carrier IP (CGNAT is common on mobile).
        if (! Cache::add('contact-event-net:'.Visitor::networkHash($request).":{$suffix}", true, now()->addMinutes(self::NETWORK_DEDUPE_MINUTES))) {
            return false;
        }

        if (! Cache::add("contact-event:{$visitor}:{$suffix}", true, now()->addMinutes(self::DEDUPE_MINUTES))) {
            return false;
        }

        RecordContactEvent::dispatch($business->id, $type, $source, $placement?->id, $visitor, now()->toImmutable());

        return true;
    }

    /**
     * @param  Collection<int, SponsoredPlacement>  $placements
     */
    public function recordImpressions(Collection $placements, Request $request): void
    {
        if ($placements->isEmpty() || Visitor::isBot($request)) {
            return;
        }

        $visitor = Visitor::dailyHash($request);

        $ids = $placements
            ->filter(fn (SponsoredPlacement $placement) => Cache::add("impression:{$visitor}:{$placement->id}", true, now()->endOfDay()))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($ids !== []) {
            RecordPlacementImpressions::dispatch($ids);
        }
    }
}
