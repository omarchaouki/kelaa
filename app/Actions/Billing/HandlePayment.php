<?php

namespace App\Actions\Billing;

use App\Enums\BillingPeriod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\PlacementStatus;
use App\Enums\SubscriptionStatus;
use App\Exceptions\SlotUnavailable;
use App\Models\Business;
use App\Models\Payment;
use App\Models\SponsoredPlacement;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentDecided;
use App\Services\Billing\InvoiceIssuer;
use App\Services\Billing\PlacementInventory;
use App\Support\Billing\BillingGate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Admin decisions on payments. Validating activates exactly what was ordered
 * (subscription or placement) and issues the invoice. Payment never touches
 * reviews or organic ranking.
 */
class HandlePayment
{
    public function __construct(
        private readonly InvoiceIssuer $invoices,
        private readonly PlacementInventory $inventory,
    ) {}

    public function validate(User $admin, Payment $payment): void
    {
        BillingGate::asAdmin($admin, function () use ($admin, $payment): void {
            DB::transaction(function () use ($admin, $payment): void {
                $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

                if ($payment->status !== PaymentStatus::Pending) {
                    throw new InvalidArgumentException('Only pending payments can be validated.');
                }

                match ($payment->purpose) {
                    PaymentPurpose::Subscription => $this->activateSubscription($payment),
                    PaymentPurpose::Placement => $this->activatePlacement($payment),
                };

                $invoice = $this->invoices->issue($payment);

                $payment->forceFill([
                    'status' => PaymentStatus::Validated,
                    'validated_by' => $admin->id,
                    'validated_at' => now(),
                    'invoice_id' => $invoice->id,
                ])->save();

                activity('billing')->performedOn($payment)->causedBy($admin)
                    ->withProperties(['amount' => $payment->amount, 'purpose' => $payment->purpose->value, 'invoice' => $invoice->number])
                    ->log('payment.validated');
            });
        });

        $payment->refresh()->loadMissing('user');
        $payment->user?->notify(new PaymentDecided($payment, validated: true));
    }

    public function reject(User $admin, Payment $payment, string $reason): void
    {
        $this->close($admin, $payment, PaymentStatus::Rejected, $reason, 'payment.rejected');

        $payment->refresh()->loadMissing('user');
        $payment->user?->notify(new PaymentDecided($payment, validated: false, reason: $reason));
    }

    /**
     * Records a refund made outside the platform and stops what it paid for.
     */
    public function refund(User $admin, Payment $payment, string $reason): void
    {
        if ($payment->status !== PaymentStatus::Validated) {
            throw new InvalidArgumentException('Only validated payments can be refunded.');
        }

        $this->close($admin, $payment, PaymentStatus::Refunded, $reason, 'payment.refunded');
    }

    private function close(User $admin, Payment $payment, PaymentStatus $status, string $reason, string $event): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required.');
        }

        BillingGate::asAdmin($admin, function () use ($admin, $payment, $status, $reason, $event): void {
            DB::transaction(function () use ($admin, $payment, $status, $reason, $event): void {
                $payment->forceFill(['status' => $status, 'rejection_reason' => mb_substr($reason, 0, 255)])->save();
                $this->cancelProducts($payment);

                activity('billing')->performedOn($payment)->causedBy($admin)
                    ->withProperties(['reason' => $reason])->log($event);
            });
        });
    }

    /**
     * Cancels the subscription/placement attached to a payment, and removes
     * the plan from the business if it came from this payment.
     */
    public function cancelProducts(Payment $payment): void
    {
        Subscription::query()->where('payment_id', $payment->id)->whereIn('status', [SubscriptionStatus::Pending, SubscriptionStatus::Active])
            ->get()
            ->each(function (Subscription $subscription): void {
                $wasActive = $subscription->status === SubscriptionStatus::Active;
                $subscription->forceFill(['status' => SubscriptionStatus::Cancelled])->save();

                if ($wasActive) {
                    $this->syncBusinessPlan(Business::query()->findOrFail($subscription->business_id));
                }
            });

        SponsoredPlacement::query()->where('payment_id', $payment->id)->whereIn('status', [PlacementStatus::Pending, PlacementStatus::Active])
            ->update(['status' => PlacementStatus::Cancelled->value]);
    }

    private function activateSubscription(Payment $payment): void
    {
        $subscription = Subscription::query()->where('payment_id', $payment->id)->with(['plan', 'business'])->firstOrFail();
        $business = $subscription->business;

        // Renewing the same plan extends it; switching plans starts now.
        $current = Subscription::query()
            ->where('business_id', $business->id)
            ->where('status', SubscriptionStatus::Active)
            ->where('ends_at', '>', now())
            ->latest('ends_at')
            ->first();

        $starts = $current !== null && $current->plan_id === $subscription->plan_id
            ? CarbonImmutable::instance($current->ends_at)
            : now()->toImmutable();

        if ($current !== null && $current->plan_id !== $subscription->plan_id) {
            $current->forceFill(['status' => SubscriptionStatus::Cancelled])->save();
        }

        $ends = $subscription->billing_period === BillingPeriod::Yearly ? $starts->addYear() : $starts->addMonthNoOverflow();

        $subscription->forceFill(['status' => SubscriptionStatus::Active, 'starts_at' => $starts, 'ends_at' => $ends])->save();
        $this->syncBusinessPlan($business);
    }

    private function activatePlacement(Payment $payment): void
    {
        $placement = SponsoredPlacement::query()->where('payment_id', $payment->id)->firstOrFail();

        // The hold may have expired before the admin validated: re-check the slot.
        if (! $this->inventory->isAvailable($placement->city_id, $placement->category_id, $placement->neighborhood_id, $placement->position, $placement->starts_at, $placement->ends_at, $placement->id)) {
            throw new SlotUnavailable(__('Cet emplacement a été attribué entre-temps : refusez ou remboursez ce paiement.'));
        }

        $placement->forceFill(['status' => PlacementStatus::Active])->save();
    }

    /**
     * The business plan mirrors its latest active subscription (none = free plan).
     */
    public function syncBusinessPlan(Business $business): void
    {
        $active = Subscription::query()
            ->where('business_id', $business->id)
            ->where('status', SubscriptionStatus::Active)
            ->where('ends_at', '>', now())
            ->latest('ends_at')
            ->first();

        $business->forceFill([
            'plan_id' => $active?->plan_id,
            'plan_expires_at' => $active?->ends_at,
        ])->save();
    }
}
