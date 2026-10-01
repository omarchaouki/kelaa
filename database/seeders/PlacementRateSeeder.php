<?php

namespace Database\Seeders;

use App\Models\PlacementRate;
use Illuminate\Database\Seeder;

/**
 * Default sponsored-slot prices (all cities, all categories). Placeholders:
 * admins refine them per city/category from the panel; never overwritten.
 */
class PlacementRateSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            1 => ['price_week' => 250, 'price_month' => 900],
            2 => ['price_week' => 180, 'price_month' => 650],
            3 => ['price_week' => 120, 'price_month' => 450],
        ];

        foreach ($defaults as $position => $prices) {
            PlacementRate::query()->firstOrCreate(
                ['city_id' => null, 'category_id' => null, 'position' => $position],
                $prices + ['neighborhood_percent' => 60, 'is_active' => true],
            );
        }
    }
}
