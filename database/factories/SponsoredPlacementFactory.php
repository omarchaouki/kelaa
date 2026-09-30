<?php

namespace Database\Factories;

use App\Enums\PlacementStatus;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\SponsoredPlacement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SponsoredPlacement>
 */
class SponsoredPlacementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'city_id' => City::factory(),
            'category_id' => Category::factory(),
            'neighborhood_id' => null,
            'position' => fake()->numberBetween(1, 3),
            'starts_at' => now()->subDay()->startOfDay(),
            'ends_at' => now()->addDays(6)->endOfDay(),
            'status' => PlacementStatus::Active,
        ];
    }
}
