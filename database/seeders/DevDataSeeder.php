<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\PharmacyDuty;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fake businesses, hours and reviews for local development.
 * Refuses to run in production.
 */
class DevDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DevDataSeeder must never run in production.');
        }

        DB::transaction(function (): void {
            $this->seedStaff();
            $this->seedDirectory();
        });
    }

    /**
     * Local-only staff accounts for the /admin panel (see README).
     */
    private function seedStaff(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin Dev',
            'phone' => '+212600000001',
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);

        User::factory()->moderator()->create([
            'name' => 'Modérateur Dev',
            'phone' => '+212600000002',
            'email' => 'moderator@example.test',
            'password' => 'password',
        ]);
    }

    private function seedDirectory(): void
    {
        $city = City::query()->with('neighborhoods')->firstOrFail();
        $categories = Category::query()->whereNotNull('parent_id')->get();
        $members = User::factory()->count(40)->create();

        foreach ($categories as $category) {
            $businesses = Business::factory()
                ->count(fake()->numberBetween(2, 6))
                ->for($city)
                ->inCategory($category)
                ->withRegularHours()
                ->state(fn () => [
                    'neighborhood_id' => $city->neighborhoods->random()?->getKey(),
                    'latitude' => $city->center_latitude + fake()->randomFloat(5, -0.04, 0.04),
                    'longitude' => $city->center_longitude + fake()->randomFloat(5, -0.04, 0.04),
                ])
                ->create();

            foreach ($businesses as $business) {
                $authors = $members->random(fake()->numberBetween(0, 12));

                foreach ($authors as $author) {
                    Review::factory()->for($business)->for($author)->create();
                }

                if ($category->slug === 'pharmacie-de-garde') {
                    PharmacyDuty::factory()->for($business)->create([
                        'date' => now()->addDays(fake()->numberBetween(0, 6))->startOfDay(),
                    ]);
                }
            }
        }
    }
}
