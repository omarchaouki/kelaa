<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Neighborhood;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Neighborhood>
 */
class NeighborhoodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Quartier '.fake()->unique()->lastName();

        return [
            'city_id' => City::factory(),
            'name' => ['fr' => $name, 'ar' => $name, 'en' => $name],
            'slug' => Str::slug($name),
            'latitude' => 33.5731 + fake()->randomFloat(4, -0.04, 0.04),
            'longitude' => -7.5898 + fake()->randomFloat(4, -0.04, 0.04),
            'is_active' => true,
        ];
    }
}
