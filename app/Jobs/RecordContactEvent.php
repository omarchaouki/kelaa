<?php

namespace App\Jobs;

use App\Enums\ContactEventSource;
use App\Enums\ContactEventType;
use App\Models\ContactEvent;
use App\Models\SponsoredPlacement;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordContactEvent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $businessId,
        public ContactEventType $type,
        public ContactEventSource $source,
        public ?int $placementId,
        public string $sessionHash,
        public CarbonImmutable $occurredAt,
    ) {}

    public function handle(): void
    {
        ContactEvent::query()->create([
            'business_id' => $this->businessId,
            'type' => $this->type,
            'source' => $this->source,
            'sponsored_placement_id' => $this->placementId,
            'session_hash' => $this->sessionHash,
            'created_at' => $this->occurredAt,
        ]);

        // A visit coming from a sponsored slot is that placement's click.
        if ($this->placementId !== null && $this->type === ContactEventType::ProfileView) {
            SponsoredPlacement::query()->whereKey($this->placementId)->increment('clicks');
        }
    }
}
