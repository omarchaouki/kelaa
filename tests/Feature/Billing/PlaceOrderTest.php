<?php

use App\Actions\Billing\PlaceOrder;
use App\Enums\BillingPeriod;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\PlacementStatus;
use App\Enums\PlacementUnit;
use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Exceptions\RateLimited;
use App\Exceptions\SlotUnavailable;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\Payment;
use App\Models\PlacementRate;
use App\Models\Plan;
use App\Models\SponsoredPlacement;
use App\Services\Billing\PlacementInventory;
use App\Services\Billing\Pricing;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\MassAssignmentException;

beforeEach(function () {
    $this->city = City::factory()->create(['slug' => 'ma-ville']);
    $this->category = Category::factory()->create();
    $this->owner = staff(Role::BusinessOwner);
    $this->business = Business::factory()->for($this->city)->inCategory($this->category)
        ->create(['owner_id' => $this->owner->id, 'is_claimed' => true]);
    $this->plan = Plan::factory()->create(['price_monthly' => '149.00', 'price_yearly' => '1490.00']);
    PlacementRate::query()->create(['position' => 1, 'price_week' => 250, 'price_month' => 900, 'neighborhood_percent' => 60, 'is_active' => true]);
    PlacementRate::query()->create(['position' => 2, 'price_week' => 180, 'price_month' => 650, 'neighborhood_percent' => 60, 'is_active' => true]);
    $this->order = app(PlaceOrder::class);
    $this->start = now()->addWeek()->startOfDay();
});

function orderSlot(Business $business, Category $category, ?Neighborhood $neighborhood = null, int $position = 1, int $weeks = 2, $start = null): Payment
{
    return app(PlaceOrder::class)->placement($business->owner, $business, $category, $neighborhood, $position, PlacementUnit::Week, $weeks, $start ?? now()->addWeek(), PaymentMethod::BankTransfer);
}

it('prices a subscription from the database and leaves it pending', function () {
    $payment = $this->order->subscription($this->owner, $this->business, $this->plan, BillingPeriod::Yearly, PaymentMethod::BankTransfer);

    expect($payment->amount)->toBe('1490.00')
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->purpose)->toBe(PaymentPurpose::Subscription)
        ->and($payment->reference)->toMatch('/^P-[A-HJ-NP-Z2-9]{8}$/')
        ->and($payment->expires_at->isFuture())->toBeTrue()
        ->and($payment->subscription->status)->toBe(SubscriptionStatus::Pending)
        ->and($this->business->fresh()->plan_id)->toBeNull();  // nothing is active before validation
});

it('never takes the amount or status from user input', function () {
    expect(fn () => new Payment(['business_id' => $this->business->id, 'amount' => '1.00']))->toThrow(MassAssignmentException::class)
        ->and(fn () => new Payment(['business_id' => $this->business->id, 'status' => PaymentStatus::Validated]))->toThrow(MassAssignmentException::class)
        ->and(fn () => new Payment(['business_id' => $this->business->id, 'invoice_id' => 99]))->toThrow(MassAssignmentException::class);
});

it('refuses free or inactive plans, other people’s listings and unknown payment methods', function () {
    $free = Plan::factory()->free()->create();

    expect(fn () => $this->order->subscription($this->owner, $this->business, $free, BillingPeriod::Monthly, PaymentMethod::BankTransfer))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $this->order->subscription(staff(Role::BusinessOwner), $this->business, $this->plan, BillingPeriod::Monthly, PaymentMethod::BankTransfer))
        ->toThrow(AuthorizationException::class);

    expect(fn () => $this->order->subscription($this->owner, $this->business, $this->plan, BillingPeriod::Monthly, PaymentMethod::Cmi))
        ->toThrow(InvalidArgumentException::class);

    expect(Payment::query()->count())->toBe(0);
});

it('prices placements per position, duration and zone', function () {
    $pricing = app(Pricing::class);
    $neighborhood = Neighborhood::factory()->for($this->city)->create();

    expect($pricing->placement($this->city, $this->category, null, 1, PlacementUnit::Week, 3))->toBe('750.00')
        ->and($pricing->placement($this->city, $this->category, null, 2, PlacementUnit::Month, 2))->toBe('1300.00')
        ->and($pricing->placement($this->city, $this->category, $neighborhood, 1, PlacementUnit::Week, 1))->toBe('150.00')
        ->and($pricing->placement($this->city, $this->category, null, 3, PlacementUnit::Week, 1))->toBeNull();  // no rate: not for sale

    // A more specific rate wins.
    PlacementRate::query()->create(['city_id' => $this->city->id, 'category_id' => $this->category->id, 'position' => 1, 'price_week' => 400, 'price_month' => 1500, 'neighborhood_percent' => 50, 'is_active' => true]);

    expect($pricing->placement($this->city, $this->category, null, 1, PlacementUnit::Week, 1))->toBe('400.00');
});

it('splits VAT out of TTC prices', function () {
    config(['billing.invoice.vat_rate' => 20, 'billing.invoice.prices_include_vat' => true]);

    expect(app(Pricing::class)->vat('1200.00'))->toBe(['subtotal' => '1000.00', 'tax_rate' => '20.00', 'tax_amount' => '200.00', 'total' => '1200.00']);

    config(['billing.invoice.prices_include_vat' => false]);

    expect(app(Pricing::class)->vat('1000.00')['total'])->toBe('1200.00');
});

it('holds the slot while the order is unpaid, so nobody else can book it', function () {
    $payment = orderSlot($this->business, $this->category);

    expect($payment->amount)->toBe('500.00')
        ->and($payment->sponsoredPlacement->status)->toBe(PlacementStatus::Pending);

    $rival = Business::factory()->for($this->city)->inCategory($this->category)->create(['owner_id' => staff(Role::BusinessOwner)->id]);

    expect(fn () => orderSlot($rival, $this->category, start: now()->addWeeks(2)))->toThrow(SlotUnavailable::class);

    // Another position, or after the booked period, is still free.
    expect(orderSlot($rival, $this->category, position: 2)->exists)->toBeTrue()
        ->and(orderSlot($rival, $this->category, start: now()->addWeeks(3))->exists)->toBeTrue();
});

it('frees the slot once the unpaid hold has expired', function () {
    orderSlot($this->business, $this->category);
    $rival = Business::factory()->for($this->city)->inCategory($this->category)->create(['owner_id' => staff(Role::BusinessOwner)->id]);

    $this->travel(config('billing.pending_hold_hours') + 1)->hours();

    expect(orderSlot($rival, $this->category)->exists)->toBeTrue();
});

it('keeps city-wide and neighborhood slots as separate inventories', function () {
    $neighborhood = Neighborhood::factory()->for($this->city)->create();
    orderSlot($this->business, $this->category);
    $rival = Business::factory()->for($this->city)->inCategory($this->category)->create(['owner_id' => staff(Role::BusinessOwner)->id]);

    expect(orderSlot($rival, $this->category, $neighborhood)->sponsoredPlacement->neighborhood_id)->toBe($neighborhood->id)
        ->and(fn () => orderSlot($rival, $this->category, $neighborhood, start: now()->addWeeks(2)))->toThrow(SlotUnavailable::class);
});

it('only sells slots in the business’s own categories and city', function () {
    $otherCategory = Category::factory()->create();
    $otherNeighborhood = Neighborhood::factory()->for(City::factory())->create();

    expect(fn () => orderSlot($this->business, $otherCategory))->toThrow(InvalidArgumentException::class)
        ->and(fn () => orderSlot($this->business, $this->category, $otherNeighborhood))->toThrow(InvalidArgumentException::class)
        ->and(fn () => orderSlot($this->business, $this->category, position: 4))->toThrow(InvalidArgumentException::class)
        ->and(fn () => orderSlot($this->business, $this->category, weeks: 13))->toThrow(InvalidArgumentException::class)
        ->and(fn () => orderSlot($this->business, $this->category, start: now()->subDays(2)))->toThrow(InvalidArgumentException::class)
        ->and(fn () => orderSlot($this->business, $this->category, start: now()->addMonths(7)))->toThrow(InvalidArgumentException::class);
});

it('computes whole-week and whole-month periods without overflow', function () {
    $inventory = app(PlacementInventory::class);

    [$starts, $ends] = $inventory->period(now()->setDate(2027, 1, 31)->setTime(15, 0), PlacementUnit::Month, 1);

    expect($starts->toDateTimeString())->toBe('2027-01-31 00:00:00')
        ->and($ends->toDateString())->toBe('2027-02-28');
});

it('shows an active placement as taken in the weekly grid', function () {
    SponsoredPlacement::factory()->create([
        'city_id' => $this->city->id, 'category_id' => $this->category->id, 'position' => 2,
        'starts_at' => now()->startOfWeek(), 'ends_at' => now()->startOfWeek()->addWeeks(2),
    ]);

    $grid = app(PlacementInventory::class)->weeklyGrid($this->city->id, $this->category->id, null, 3);

    expect($grid[0]['positions'])->toBe([1 => true, 2 => false, 3 => true])
        ->and($grid[2]['positions'][2])->toBeTrue();
});

it('limits how many orders an owner can place per day', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->order->subscription($this->owner, $this->business, $this->plan, BillingPeriod::Monthly, PaymentMethod::BankTransfer);
    }

    $this->order->subscription($this->owner, $this->business, $this->plan, BillingPeriod::Monthly, PaymentMethod::BankTransfer);
})->throws(RateLimited::class);
