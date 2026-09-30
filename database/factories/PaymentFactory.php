<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Business;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'amount' => '199.00',
            'currency' => 'MAD',
            'method' => PaymentMethod::BankTransfer,
            'reference' => 'PAY-'.strtoupper(fake()->unique()->bothify('????####')),
            'status' => PaymentStatus::Pending,
        ];
    }
}
