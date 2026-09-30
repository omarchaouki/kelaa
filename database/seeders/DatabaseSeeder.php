<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference data, safe to run in every environment (idempotent).
     * Fake data only outside production: `php artisan db:seed --class=DevDataSeeder`
     * or automatically here in the local environment.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
            PlanSeeder::class,
            PageSeeder::class,
            SearchSynonymSeeder::class,
        ]);

        if (app()->isLocal()) {
            $this->call(DevDataSeeder::class);
        }
    }
}
