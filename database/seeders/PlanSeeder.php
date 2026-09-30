<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Creates the three launch plans once. Prices are placeholders: they are
 * managed from the admin panel and never overwritten by this seeder.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free',
                'name' => ['fr' => 'Gratuit', 'ar' => 'مجاني', 'en' => 'Free'],
                'price_monthly' => 0,
                'price_yearly' => 0,
                'is_default' => true,
                'sort_order' => 0,
                'features' => [
                    'max_photos' => 3, 'show_stats' => false, 'highlighted_card' => false,
                    'logo_and_cover' => false, 'remove_competitor_ads' => false, 'priority_support' => false,
                ],
            ],
            [
                'slug' => 'pro',
                'name' => ['fr' => 'Pro', 'ar' => 'برو', 'en' => 'Pro'],
                'price_monthly' => 149,
                'price_yearly' => 1490,
                'is_default' => false,
                'sort_order' => 1,
                'features' => [
                    'max_photos' => 15, 'show_stats' => true, 'highlighted_card' => true,
                    'logo_and_cover' => true, 'remove_competitor_ads' => false, 'priority_support' => false,
                ],
            ],
            [
                'slug' => 'premium',
                'name' => ['fr' => 'Premium', 'ar' => 'بريميوم', 'en' => 'Premium'],
                'price_monthly' => 299,
                'price_yearly' => 2990,
                'is_default' => false,
                'sort_order' => 2,
                'features' => [
                    'max_photos' => 40, 'show_stats' => true, 'highlighted_card' => true,
                    'logo_and_cover' => true, 'remove_competitor_ads' => true, 'priority_support' => true,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->firstOrCreate(['slug' => $plan['slug']], $plan + ['is_active' => true]);
        }
    }
}
