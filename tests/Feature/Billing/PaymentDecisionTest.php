<?php

use App\Actions\Billing\HandlePayment;
use App\Actions\Billing\PlaceOrder;
use App\Enums\BillingPeriod;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PlacementStatus;
use App\Enums\PlacementUnit;
use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Exceptions\SlotUnavailable;
use App\Exceptions\UnauthorizedStatusChange;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PlacementRate;
use App\Models\Plan;
use App\Models\SponsoredPlacement;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentDecided;
use App\Support\Billing\BillingGate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->city = City::factory()->create(['slug' => 'ma-ville']);
    $this->category = Category::factory()->create();
    $this->owner = staff(Role::BusinessOwner);
    $this->business = Business::factory()->for($this->city)->inCategory($this->category)
        ->create(['owner_id' => $this->owner->id, 'is_claimed' => true, 'legal_name' => 'Garage Atlas SARL', 'ice' => '001234567000089']);
    $this->plan = Plan::factory()->create(['price_monthly' => '120.00', 'price_yearly' => '1200.00']);
    PlacementRate::query()->create(['position' => 1, 'price_week' => 240, 'price_month' => 900, 'neighborhood_percent' => 60, 'is_active' => true]);
    $this->admin = staff(Role::Admin);
    $this->payments = app(HandlePayment::class);
});

function orderPlan(Business $business, Plan $plan, BillingPeriod $period = BillingPeriod::Monthly): Payment
{
    return app(PlaceOrder::class)->subscription($business->owner, $business, $plan, $period, PaymentMethod::BankTransfer);
}

it('activates the subscription, sets the plan and issues a paid invoice on validation', function () {
    $payment = orderPlan($this->business, $this->plan);

    $this->payments->validate($this->admin, $payment);

    $payment->refresh();
    $subscription = $payment->subscription;
    $business = $this->business->fresh();

    expect($payment->status)->toBe(PaymentStatus::Validated)
        ->and($payment->validated_by)->toBe($this->admin->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->ends_at->toDateString())->toBe(now()->addMonthNoOverflow()->toDateString())
        ->and($business->plan_id)->toBe($this->plan->id)
        ->and($business->plan_expires_at->equalTo($subscription->ends_at))->toBeTrue()
        ->and($payment->invoice->client_name)->toBe('Garage Atlas SARL')
        ->and($payment->invoice->client_ice)->toBe('001234567000089')
        ->and($payment->invoice->total)->toBe('120.00')
        ->and($payment->invoice->subtotal)->toBe('100.00')
        ->and($payment->invoice->tax_amount)->toBe('20.00');

    Notification::assertSentTo($this->owner, PaymentDecided::class);
});

it('extends a renewal of the same plan from the current end date', function () {
    $this->payments->validate($this->admin, orderPlan($this->business, $this->plan));
    $firstEnd = Subscription::query()->where('status', SubscriptionStatus::Active)->value('ends_at');

    $renewal = orderPlan($this->business, $this->plan, BillingPeriod::Yearly);
    $this->payments->validate($this->admin, $renewal);

    $renewed = $renewal->fresh()->subscription;

    expect($renewed->starts_at->toDateTimeString())->toBe(Carbon\Carbon::parse($firstEnd)->toDateTimeString())
        ->and($renewed->ends_at->toDateString())->toBe(Carbon\Carbon::parse($firstEnd)->addYear()->toDateString())
        ->and($this->business->fresh()->plan_expires_at->toDateString())->toBe($renewed->ends_at->toDateString());
});

it('cancels the current plan when switching to another one', function () {
    $first = orderPlan($this->business, $this->plan);
    $this->payments->validate($this->admin, $first);

    $premium = Plan::factory()->create(['price_monthly' => '300.00']);
    $switch = orderPlan($this->business, $premium);
    $this->payments->validate($this->admin, $switch);

    expect($first->fresh()->subscription->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($switch->fresh()->subscription->starts_at->isToday())->toBeTrue()
        ->and($this->business->fresh()->plan_id)->toBe($premium->id);
});

it('activates nothing when a payment is rejected, and frees the slot', function () {
    $payment = app(PlaceOrder::class)->placement($this->owner, $this->business, $this->category, null, 1, PlacementUnit::Week, 1, now()->addDay(), PaymentMethod::CashAgency);

    $this->payments->reject($this->admin, $payment, 'Aucun versement reçu');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Rejected)
        ->and($payment->fresh()->rejection_reason)->toBe('Aucun versement reçu')
        ->and($payment->fresh()->invoice_id)->toBeNull()
        ->and($payment->sponsoredPlacement->fresh()->status)->toBe(PlacementStatus::Cancelled)
        ->and(Invoice::query()->count())->toBe(0);

    Notification::assertSentTo($this->owner, PaymentDecided::class);

    $rival = Business::factory()->for($this->city)->inCategory($this->category)->create(['owner_id' => staff(Role::BusinessOwner)->id]);
    expect(app(PlaceOrder::class)->placement($rival->owner, $rival, $this->category, null, 1, PlacementUnit::Week, 1, now()->addDay(), PaymentMethod::CashAgency)->exists)->toBeTrue();
});

it('activates a validated placement and stops it on refund, downgrading the plan too', function () {
    $placementPayment = app(PlaceOrder::class)->placement($this->owner, $this->business, $this->category, null, 1, PlacementUnit::Week, 2, now(), PaymentMethod::BankTransfer);
    $this->payments->validate($this->admin, $placementPayment);

    expect($placementPayment->sponsoredPlacement->fresh()->status)->toBe(PlacementStatus::Active);

    $planPayment = orderPlan($this->business, $this->plan);
    $this->payments->validate($this->admin, $planPayment);
    $this->payments->refund($this->admin, $planPayment, 'Remboursé par virement');
    $this->payments->refund($this->admin, $placementPayment, 'Remboursé par virement');

    expect($planPayment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($this->business->fresh()->plan_id)->toBeNull()
        ->and($placementPayment->sponsoredPlacement->fresh()->status)->toBe(PlacementStatus::Cancelled)
        ->and($planPayment->fresh()->invoice)->not->toBeNull();  // the invoice stays
});

it('refuses to validate a placement whose slot was given away after the hold expired', function () {
    $payment = app(PlaceOrder::class)->placement($this->owner, $this->business, $this->category, null, 1, PlacementUnit::Week, 1, now()->addDay(), PaymentMethod::BankTransfer);
    $this->travel(config('billing.pending_hold_hours') + 1)->hours();

    $rival = Business::factory()->for($this->city)->inCategory($this->category)->create(['owner_id' => staff(Role::BusinessOwner)->id]);
    $this->payments->validate($this->admin, app(PlaceOrder::class)->placement($rival->owner, $rival, $this->category, null, 1, PlacementUnit::Week, 1, now(), PaymentMethod::BankTransfer));

    expect(fn () => $this->payments->validate($this->admin, $payment))->toThrow(SlotUnavailable::class)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and(SponsoredPlacement::query()->where('status', PlacementStatus::Active)->count())->toBe(1);
});

it('only lets admins with payment rights validate', function () {
    $payment = orderPlan($this->business, $this->plan);

    expect(fn () => $this->payments->validate(staff(Role::Moderator), $payment))->toThrow(AuthorizationException::class)
        ->and(fn () => $this->payments->validate($this->owner, $payment))->toThrow(AuthorizationException::class)
        ->and(fn () => $this->payments->validate(User::factory()->admin()->banned()->create(), $payment))->toThrow(AuthorizationException::class)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($this->business->fresh()->plan_id)->toBeNull();
});

it('blocks any direct status or amount change outside the billing gate', function () {
    $payment = orderPlan($this->business, $this->plan);

    expect(fn () => $payment->forceFill(['status' => PaymentStatus::Validated])->save())->toThrow(UnauthorizedStatusChange::class)
        ->and(fn () => $payment->fresh()->forceFill(['amount' => '1.00'])->save())->toThrow(UnauthorizedStatusChange::class)
        ->and(fn () => BillingGate::asAdmin(staff(Role::Moderator), fn () => null))->toThrow(AuthorizationException::class);
});

it('never validates twice', function () {
    $payment = orderPlan($this->business, $this->plan);
    $this->payments->validate($this->admin, $payment);

    expect(fn () => $this->payments->validate($this->admin, $payment))->toThrow(InvalidArgumentException::class)
        ->and(Invoice::query()->count())->toBe(1);
});

it('numbers invoices sequentially per year and restarts each January', function () {
    $this->travelTo(now()->setDate(2026, 12, 30));

    foreach (range(1, 3) as $i) {
        $this->payments->validate($this->admin, orderPlan($this->business, $this->plan));
    }

    $this->travelTo(now()->setDate(2027, 1, 2));
    $this->payments->validate($this->admin, orderPlan($this->business, $this->plan));

    expect(Invoice::query()->orderBy('id')->pluck('number')->all())
        ->toBe(['F-2026-00001', 'F-2026-00002', 'F-2026-00003', 'F-2027-00001']);
});

it('keeps an issued invoice unchanged when the business edits its legal details', function () {
    $payment = orderPlan($this->business, $this->plan);
    $this->payments->validate($this->admin, $payment);

    $this->business->fill(['legal_name' => 'Autre nom', 'ice' => '999999999999999'])->save();
    $invoice = $payment->fresh()->invoice;

    expect($invoice->client_name)->toBe('Garage Atlas SARL')
        ->and($invoice->client_ice)->toBe('001234567000089')
        ->and(fn () => $invoice->update(['total' => '1.00']))->toThrow(LogicException::class)
        ->and(fn () => $invoice->delete())->toThrow(LogicException::class);
});

it('writes invoices in French whatever the admin’s language', function () {
    app()->setLocale('ar');
    $payment = orderPlan($this->business, $this->plan);
    $this->payments->validate($this->admin, $payment);

    expect($payment->fresh()->invoice->lines[0]['description'])->toStartWith('Abonnement');
});
