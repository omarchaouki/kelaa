<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name' => ['fr' => $name, 'ar' => $name, 'en' => $name],
            'slug' => Str::slug($name),
            'center_latitude' => 33.5731,
            'center_longitude' => -7.5898,
            'default_zoom' => 13,
            'timezone' => 'Africa/Casablanca',
            'is_active' => true,
        ];
    }
}
