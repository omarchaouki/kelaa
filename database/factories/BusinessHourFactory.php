<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessHour>
 */
class BusinessHourFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'day_of_week' => fake()->numberBetween(1, 7),
            'opens_at' => '09:00',
            'closes_at' => '19:00',
            'is_closed' => false,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'opens_at' => null,
            'closes_at' => null,
            'is_closed' => true,
        ]);
    }
}
