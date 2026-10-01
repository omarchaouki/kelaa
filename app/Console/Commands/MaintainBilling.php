<?php

namespace App\Console\Commands;

use App\Actions\Billing\HandlePayment;
use App\Enums\PaymentStatus;
use App\Enums\PlacementStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Payment;
use App\Models\SponsoredPlacement;
use App\Models\Subscription;
use App\Notifications\ServiceExpiring;
use App\Support\Billing\BillingGate;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('billing:maintain')]
#[Description('Expire unpaid orders, end finished plans and placements, send expiry reminders')]
class MaintainBilling extends Command
{
    public function handle(HandlePayment $payments): int
    {
        $this->expireUnpaidOrders($payments);
        $this->expireSubscriptions($payments);
        $this->expirePlacements();
        $this->sendReminders();

        return self::SUCCESS;
    }

    /**
     * Unpaid orders are cancelled so their sponsored slot becomes free again.
     */
    private function expireUnpaidOrders(HandlePayment $payments): void
    {
        Payment::query()
            ->where('status', PaymentStatus::Pending)
            ->where('expires_at', '<=', now())
            ->whereDoesntHave('media', fn ($q) => $q->where('collection_name', 'receipt'))  // a receipt waits for an admin
            ->each(function (Payment $payment) use ($payments): void {
                BillingGate::asSystem(fn () => DB::transaction(function () use ($payment, $payments): void {
                    $payment->forceFill(['status' => PaymentStatus::Rejected, 'rejection_reason' => 'Expired: no payment received.'])->save();
                    $payments->cancelProducts($payment);
                }));

                activity('billing')->performedOn($payment)->log('payment.expired');
            });
    }

    /**
     * Finished subscriptions end; the business falls back to the free plan
     * (extra photos are hidden, never deleted).
     */
    private function expireSubscriptions(HandlePayment $payments): void
    {
        Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->where('ends_at', '<=', now())
            ->each(function (Subscription $subscription) use ($payments): void {
                $subscription->forceFill(['status' => SubscriptionStatus::Expired])->save();
                $payments->syncBusinessPlan(Business::query()->findOrFail($subscription->business_id));
            });
    }

    private function expirePlacements(): void
    {
        SponsoredPlacement::query()
            ->where('status', PlacementStatus::Active)
            ->where('ends_at', '<=', now())
            ->update(['status' => PlacementStatus::Expired->value]);
    }

    private function sendReminders(): void
    {
        foreach ((array) config('billing.reminder_days') as $days) {
            $column = $days >= 7 ? 'reminded_7d_at' : 'reminded_1d_at';
            // The 7-day reminder skips what ends within a day: that gets the 1-day one only.
            $window = [$days >= 7 ? now()->addDay() : now(), now()->addDays((int) $days)];

            Subscription::query()
                ->where('status', SubscriptionStatus::Active)
                ->whereBetween('ends_at', $window)
                ->whereNull($column)
                ->with('business.owner')
                ->each(function (Subscription $subscription) use ($column): void {
                    // A renewal already paid for the same plan needs no reminder.
                    $renewed = Subscription::query()->where('business_id', $subscription->business_id)
                        ->where('status', SubscriptionStatus::Active)->where('ends_at', '>', $subscription->ends_at)->exists();

                    if (! $renewed && $subscription->ends_at !== null) {
                        $subscription->business->owner?->notify(new ServiceExpiring('plan', $subscription->business_id, $subscription->business->name, $subscription->ends_at));
                    }

                    $subscription->forceFill([$column => now()])->save();
                });

            SponsoredPlacement::query()
                ->where('status', PlacementStatus::Active)
                ->whereBetween('ends_at', $window)
                ->whereNull($column)
                ->with('business.owner')
                ->each(function (SponsoredPlacement $placement) use ($column): void {
                    $placement->business->owner?->notify(new ServiceExpiring('placement', $placement->business_id, $placement->business->name, $placement->ends_at));
                    $placement->forceFill([$column => now()])->save();
                });
        }
    }
}
