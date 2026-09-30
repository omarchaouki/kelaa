<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessSpecialHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessSpecialHour>
 */
class BusinessSpecialHourFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 60))->startOfDay();

        return [
            'business_id' => Business::factory(),
            'label' => ['fr' => 'Horaires Ramadan', 'ar' => 'توقيت رمضان', 'en' => 'Ramadan hours'],
            'starts_on' => $start,
            'ends_on' => $start->copy()->addDays(29),
            'opens_at' => '10:00',
            'closes_at' => '16:00',
            'is_closed' => false,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'label' => ['fr' => 'Aïd', 'ar' => 'العيد', 'en' => 'Eid'],
            'opens_at' => null,
            'closes_at' => null,
            'is_closed' => true,
        ]);
    }
}
