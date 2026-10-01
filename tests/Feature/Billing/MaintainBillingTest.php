<?php

use App\Actions\Billing\HandlePayment;
use App\Actions\Billing\PlaceOrder;
use App\Actions\Billing\UploadReceipt;
use App\Enums\BillingPeriod;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PlacementStatus;
use App\Enums\PlacementUnit;
use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\PlacementRate;
use App\Models\Plan;
use App\Models\SponsoredPlacement;
use App\Models\Subscription;
use App\Notifications\ServiceExpiring;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Notification::fake();
    Storage::fake('local');
    $this->category = Category::factory()->create();
    $this->owner = staff(Role::BusinessOwner);
    $this->business = Business::factory()->for(City::factory()->create(['slug' => 'ma-ville']))->inCategory($this->category)
        ->create(['owner_id' => $this->owner->id, 'is_claimed' => true]);
    $this->plan = Plan::factory()->create();
    PlacementRate::query()->create(['position' => 1, 'price_week' => 240, 'price_month' => 900, 'neighborhood_percent' => 60, 'is_active' => true]);
});

it('cancels unpaid orders after the hold, but waits for an admin when a receipt was sent', function () {
    $orders = app(PlaceOrder::class);
    $unpaid = $orders->placement($this->owner, $this->business, $this->category, null, 1, PlacementUnit::Week, 1, now()->addDay(), PaymentMethod::BankTransfer);
    $withReceipt = $orders->subscription($this->owner, $this->business, $this->plan, BillingPeriod::Monthly, PaymentMethod::BankTransfer);
    app(UploadReceipt::class)($this->owner, $withReceipt, UploadedFile::fake()->createWithContent('recu.pdf', "%PDF-1.4\n%fake"));

    $this->travel(config('billing.pending_hold_hours') + 1)->hours();
    $this->artisan('billing:maintain')->assertSuccessful();

    expect($unpaid->fresh()->status)->toBe(PaymentStatus::Rejected)
        ->and($unpaid->sponsoredPlacement->fresh()->status)->toBe(PlacementStatus::Cancelled)
        ->and($withReceipt->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($withReceipt->fresh()->expires_at->isFuture())->toBeTrue();
});

it('ends finished subscriptions and placements, and returns the business to the free plan', function () {
    app(HandlePayment::class)->validate(staff(Role::Admin), app(PlaceOrder::class)->subscription($this->owner, $this->business, $this->plan, BillingPeriod::Monthly, PaymentMethod::BankTransfer));
    $placement = SponsoredPlacement::factory()->create(['business_id' => $this->business->id, 'ends_at' => now()->addDays(3)]);

    expect($this->business->fresh()->plan_id)->toBe($this->plan->id);

    $this->travel(32)->days();
    $this->artisan('billing:maintain')->assertSuccessful();

    expect(Subscription::query()->sole()->status)->toBe(SubscriptionStatus::Expired)
        ->and($this->business->fresh()->plan_id)->toBeNull()
        ->and($this->business->fresh()->plan_expires_at)->toBeNull()
        ->and($placement->fresh()->status)->toBe(PlacementStatus::Expired);
});

it('reminds the owner 7 days and 1 day before the end, once each', function () {
    $subscription = Subscription::factory()->create(['business_id' => $this->business->id, 'ends_at' => now()->addDays(10)]);

    $this->artisan('billing:maintain');
    Notification::assertNothingSent();

    $this->travel(4)->days();
    $this->artisan('billing:maintain');
    $this->artisan('billing:maintain');
    Notification::assertSentToTimes($this->owner, ServiceExpiring::class, 1);

    $this->travel(132)->hours();
    $this->artisan('billing:maintain');
    $this->artisan('billing:maintain');
    Notification::assertSentToTimes($this->owner, ServiceExpiring::class, 2);

    expect($subscription->fresh()->reminded_7d_at)->not->toBeNull()
        ->and($subscription->fresh()->reminded_1d_at)->not->toBeNull();
});

it('sends only the last reminder for a service that already ends tomorrow', function () {
    Subscription::factory()->create(['business_id' => $this->business->id, 'ends_at' => now()->addHours(20)]);

    $this->artisan('billing:maintain');

    Notification::assertSentToTimes($this->owner, ServiceExpiring::class, 1);
});

it('does not remind an owner who already renewed', function () {
    Subscription::factory()->create(['business_id' => $this->business->id, 'ends_at' => now()->addDays(5)]);
    Subscription::factory()->create(['business_id' => $this->business->id, 'starts_at' => now()->addDays(5), 'ends_at' => now()->addDays(35)]);

    $this->artisan('billing:maintain');

    Notification::assertNothingSentTo($this->owner);
});

it('is scheduled every hour', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains((string) $event->command, 'billing:maintain'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 * * * *');
});
