<?php

use App\Enums\ContactEventSource;
use App\Enums\ContactEventType;
use App\Jobs\AggregateContactEvents;
use App\Models\Business;
use App\Models\BusinessStatDaily;
use App\Models\ContactEvent;
use App\Models\OtpCode;
use App\Models\SponsoredPlacement;

const BROWSER = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/130 Mobile Safari/537.36';

beforeEach(function () {
    $this->business = Business::factory()->create();
});

function beacon(array $data, string $agent = BROWSER)
{
    return test()->withHeader('User-Agent', $agent)->post('/fr/events', $data);
}

it('records a contact click without storing the IP address', function () {
    beacon(['business' => $this->business->id, 'type' => 'whatsapp_click', 'source' => 'search'])->assertNoContent();

    $event = ContactEvent::query()->sole();

    expect($event->type)->toBe(ContactEventType::WhatsappClick)
        ->and($event->source)->toBe(ContactEventSource::Search)
        ->and($event->session_hash)->toHaveLength(64)
        ->and($event->session_hash)->not->toContain('127.0.0.1');
});

it('counts the same click once per visitor within the window', function () {
    beacon(['business' => $this->business->id, 'type' => 'call_click']);
    beacon(['business' => $this->business->id, 'type' => 'call_click']);
    beacon(['business' => $this->business->id, 'type' => 'whatsapp_click']);

    expect(ContactEvent::query()->count())->toBe(2);
});

it('ignores bots, unknown businesses and unpublished businesses', function () {
    $pending = Business::factory()->pending()->create();

    beacon(['business' => $this->business->id, 'type' => 'call_click'], 'Googlebot/2.1');
    beacon(['business' => 999999, 'type' => 'call_click']);
    beacon(['business' => $pending->id, 'type' => 'call_click']);

    expect(ContactEvent::query()->count())->toBe(0);
});

it('refuses profile views and unknown types from the browser', function () {
    beacon(['business' => $this->business->id, 'type' => 'profile_view'])->assertSessionHasErrors('type');
    beacon(['business' => $this->business->id, 'type' => 'hack'])->assertSessionHasErrors('type');

    expect(ContactEvent::query()->count())->toBe(0);
});

it('sits behind the CSRF-protected web middleware group', function () {
    // Laravel skips CSRF verification while running tests, so check the route's stack instead.
    $route = app('router')->getRoutes()->getByName('contact-events.store');

    expect($route->gatherMiddleware())->toContain('web');
});

it('throttles the endpoint per IP', function () {
    config(['security.rate_limits.contact_events' => 3]);

    foreach (range(1, 3) as $i) {
        beacon(['business' => $this->business->id, 'type' => 'call_click'])->assertNoContent();
    }

    beacon(['business' => $this->business->id, 'type' => 'call_click'])->assertTooManyRequests();
});

it('does not let a click be attributed to another business placement', function () {
    $foreign = SponsoredPlacement::factory()->create();

    beacon(['business' => $this->business->id, 'type' => 'call_click', 'source' => 'sponsored', 'placement' => $foreign->id]);

    $event = ContactEvent::query()->sole();
    expect($event->sponsored_placement_id)->toBeNull()
        ->and($event->source)->toBe(ContactEventSource::Organic);
});

it('aggregates a day of events idempotently', function () {
    $day = now()->subDay()->startOfDay();
    ContactEvent::factory()->count(3)->create(['business_id' => $this->business->id, 'type' => ContactEventType::CallClick, 'source' => ContactEventSource::Organic, 'session_hash' => 'a', 'created_at' => $day->copy()->addHours(10)]);
    ContactEvent::factory()->create(['business_id' => $this->business->id, 'type' => ContactEventType::CallClick, 'source' => ContactEventSource::Organic, 'session_hash' => 'b', 'created_at' => $day->copy()->addHours(11)]);
    ContactEvent::factory()->create(['business_id' => $this->business->id, 'type' => ContactEventType::CallClick, 'source' => ContactEventSource::Organic, 'created_at' => now()]);

    AggregateContactEvents::forYesterday()->handle();
    AggregateContactEvents::forYesterday()->handle();

    $row = BusinessStatDaily::query()->sole();
    expect($row->count)->toBe(4)->and($row->unique_sessions)->toBe(2)->and($row->date->toDateString())->toBe($day->toDateString());
});

it('prunes expired OTP codes and old raw events', function () {
    OtpCode::query()->create(['phone' => '+212612345678', 'purpose' => 'login', 'code_hash' => 'x', 'expires_at' => now()->subDays(2)]);
    OtpCode::query()->create(['phone' => '+212612345678', 'purpose' => 'login', 'code_hash' => 'y', 'expires_at' => now()->addMinutes(5)]);
    ContactEvent::factory()->create(['business_id' => $this->business->id, 'created_at' => now()->subMonths(14)]);
    ContactEvent::factory()->create(['business_id' => $this->business->id, 'created_at' => now()->subMonth()]);

    $this->artisan('model:prune', ['--model' => [OtpCode::class, ContactEvent::class]])->assertSuccessful();

    expect(OtpCode::query()->count())->toBe(1)->and(ContactEvent::query()->count())->toBe(1);
});
