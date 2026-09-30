<?php

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'plan_id' => Plan::factory(),
            'billing_period' => BillingPeriod::Monthly,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(20),
            'status' => SubscriptionStatus::Active,
        ];
    }
}
