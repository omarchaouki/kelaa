<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference data and the real places of El Kelaa (OpenStreetMap), safe to
     * run in every environment (idempotent). Fake data only on request:
     * `php artisan db:seed --class=DevDataSeeder` (refuses production).
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
            PlanSeeder::class,
            PlacementRateSeeder::class,
            PageSeeder::class,
            SearchSynonymSeeder::class,
            KelaaBusinessSeeder::class,
        ]);
    }
}
