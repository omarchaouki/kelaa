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
                ->state(fn () => array_filter([
                    'neighborhood_id' => $city->neighborhoods->isEmpty() ? null : $city->neighborhoods->random()->getKey(),
                    'latitude' => $city->center_latitude + fake()->randomFloat(5, -0.04, 0.04),
                    'longitude' => $city->center_longitude + fake()->randomFloat(5, -0.04, 0.04),
                    'name' => $category->slug === 'pharmacie-de-garde' ? 'Pharmacie '.fake()->lastName() : null,
                ]))
                ->create();

            foreach ($businesses as $business) {
                $authors = $members->random(fake()->numberBetween(0, 12));

                foreach ($authors as $author) {
                    Review::factory()->for($business)->for($author)->create();
                }

                if ($category->slug === 'pharmacie-de-garde') {
                    $date = now()->addDays(fake()->numberBetween(0, 6))->startOfDay();
                    PharmacyDuty::factory()->for($business)->create([
                        'date' => $date,
                        'starts_at' => $date->copy()->setTime(20, 0),
                        'ends_at' => $date->copy()->addDay()->setTime(9, 0),
                    ]);
                }
            }
        }

        $this->approximateRatings();
    }

    /**
     * Fills the rating columns for fake data. The real computation (queued job
     * with the Bayesian ranking service) arrives with reviews in phase 3.
     */
    private function approximateRatings(): void
    {
        $stats = Review::query()->published()->toBase()
            ->selectRaw('business_id, avg(rating) as average, count(*) as total')
            ->groupBy('business_id')
            ->get();

        $globalAverage = (float) Review::query()->published()->avg('rating') ?: 3.5;
        $m = (float) config('ranking.bayesian_m');

        foreach ($stats as $row) {
            $v = (int) $row->total;
            $r = (float) $row->average;

            Business::query()->whereKey($row->business_id)->update([
                'rating_avg' => round($r, 2),
                'rating_count' => $v,
                'bayesian_score' => round(($v / ($v + $m)) * $r + ($m / ($v + $m)) * $globalAverage, 4),
            ]);
        }
    }
}
