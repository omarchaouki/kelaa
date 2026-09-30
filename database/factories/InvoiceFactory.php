<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) now()->format('Y');
        $sequence = fake()->unique()->numberBetween(1, 99999);

        return [
            'number' => sprintf('F-%d-%05d', $year, $sequence),
            'year' => $year,
            'sequence' => $sequence,
            'business_id' => Business::factory(),
            'client_name' => fake()->company(),
            'client_ice' => fake()->numerify('###############'),
            'client_address' => fake()->address(),
            'lines' => [['description' => 'Abonnement Pro (1 mois)', 'quantity' => 1, 'unit_price' => '199.00', 'total' => '199.00']],
            'subtotal' => '199.00',
            'tax_rate' => '0.00',
            'tax_amount' => '0.00',
            'total' => '199.00',
            'currency' => 'MAD',
            'issued_at' => now(),
        ];
    }
}
