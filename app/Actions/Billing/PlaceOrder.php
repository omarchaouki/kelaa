<?php

namespace App\Actions\Billing;

use App\Enums\BillingPeriod;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\PlacementStatus;
use App\Enums\PlacementUnit;
use App\Enums\SubscriptionStatus;
use App\Exceptions\RateLimited;
use App\Exceptions\SlotUnavailable;
use App\Models\Business;
use App\Models\Category;
use App\Models\Neighborhood;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\SponsoredPlacement;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\PlacementInventory;
use App\Services\Billing\Pricing;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates a pending payment and the pending product it pays for. Prices are
 * read from the database, never from the request. Nothing is activated
 * until an admin validates the payment.
 */
class PlaceOrder
{
    public const MAX_PLACEMENT_WEEKS = 12;

    public const MAX_PLACEMENT_MONTHS = 6;

    public function __construct(
        private readonly Pricing $pricing,
        private readonly PlacementInventory $inventory,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws RateLimited
     */
    public function subscription(User $owner, Business $business, Plan $plan, BillingPeriod $period, PaymentMethod $method): Payment
    {
        $this->authorize($owner, $business, $method);

        if (! $plan->is_active || $plan->isFree()) {
            throw new InvalidArgumentException('This plan cannot be purchased.');
        }

        $amount = $this->pricing->plan($plan, $period);

        return DB::transaction(function () use ($owner, $business, $plan, $period, $method, $amount): Payment {
            $payment = $this->payment($owner, $business, $method, PaymentPurpose::Subscription, $amount);

            $subscription = new Subscription([
                'business_id' => $business->id,
                'plan_id' => $plan->id,
                'billing_period' => $period,
                'payment_id' => $payment->id,
            ]);
            $subscription->forceFill(['status' => SubscriptionStatus::Pending, 'amount' => $amount])->save();

            return $payment;
        });
    }

    /**
     * @throws AuthorizationException
     * @throws RateLimited
     * @throws SlotUnavailable
     */
    public function placement(User $owner, Business $business, Category $category, ?Neighborhood $neighborhood, int $position, PlacementUnit $unit, int $units, CarbonInterface $start, PaymentMethod $method): Payment
    {
        $this->authorize($owner, $business, $method);

        // A business can only sponsor itself in its own categories and city.
        if (! $business->categories()->whereKey($category->id)->exists()) {
            throw new InvalidArgumentException('The business is not in this category.');
        }

        if ($neighborhood !== null && $neighborhood->city_id !== $business->city_id) {
            throw new InvalidArgumentException('Neighborhood of another city.');
        }

        $maxUnits = $unit === PlacementUnit::Month ? self::MAX_PLACEMENT_MONTHS : self::MAX_PLACEMENT_WEEKS;

        if ($position < 1 || $position > 3 || $units < 1 || $units > $maxUnits) {
            throw new InvalidArgumentException('Invalid position or duration.');
        }

        if ($start->lt(now()->startOfDay()) || $start->gt(now()->addMonths(6))) {
            throw new InvalidArgumentException('Invalid start date.');
        }

        $business->loadMissing('city');
        $amount = $this->pricing->placement($business->city, $category, $neighborhood, $position, $unit, $units)
            ?? throw new SlotUnavailable(__('Cet emplacement n’est pas en vente.'));

        [$starts, $ends] = $this->inventory->period($start, $unit, $units);

        // One booking at a time per slot: the availability check and the
        // insert happen under the same lock.
        $lock = Cache::lock("placement-slot:{$business->city_id}:{$category->id}:".($neighborhood->id ?? 'city').":{$position}", 10);

        return $lock->block(5, fn () => DB::transaction(function () use ($owner, $business, $category, $neighborhood, $position, $unit, $units, $starts, $ends, $method, $amount): Payment {
            if (! $this->inventory->isAvailable($business->city_id, $category->id, $neighborhood?->id, $position, $starts, $ends)) {
                throw new SlotUnavailable(__('Cet emplacement est déjà réservé sur cette période.'));
            }

            $payment = $this->payment($owner, $business, $method, PaymentPurpose::Placement, $amount);

            $placement = new SponsoredPlacement([
                'business_id' => $business->id,
                'city_id' => $business->city_id,
                'category_id' => $category->id,
                'neighborhood_id' => $neighborhood?->id,
                'position' => $position,
                'starts_at' => $starts,
                'ends_at' => $ends,
                'payment_id' => $payment->id,
            ]);
            $placement->forceFill(['status' => PlacementStatus::Pending, 'billing_unit' => $unit, 'units' => $units])->save();

            return $payment;
        }));
    }

    /**
     * @throws AuthorizationException
     * @throws RateLimited
     */
    private function authorize(User $owner, Business $business, PaymentMethod $method): void
    {
        if (! $owner->can('manage', $business) || ! $business->isPublished()) {
            throw new AuthorizationException;
        }

        if (! in_array($method, [PaymentMethod::BankTransfer, PaymentMethod::CashAgency], true)) {
            throw new InvalidArgumentException('Payment method not available.');
        }

        RateLimited::guard('orders:'.$owner->id, 10, 86400);
    }

    private function payment(User $owner, Business $business, PaymentMethod $method, PaymentPurpose $purpose, string $amount): Payment
    {
        $payment = new Payment(['business_id' => $business->id, 'user_id' => $owner->id, 'method' => $method]);
        $payment->forceFill([
            'amount' => $amount,
            'currency' => config('billing.currency'),
            'purpose' => $purpose,
            'reference' => $this->reference(),
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->addHours((int) config('billing.pending_hold_hours')),
        ])->save();

        return $payment;
    }

    /**
     * Short, unguessable, easy to type on a transfer form ("P-7K3M9Q2X").
     */
    private function reference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';

            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $reference = 'P-'.$code;
        } while (Payment::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
