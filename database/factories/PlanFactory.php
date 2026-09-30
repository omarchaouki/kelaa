<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['fr' => 'Pro', 'ar' => 'برو', 'en' => 'Pro'],
            'slug' => 'plan-'.fake()->unique()->numberBetween(1, 99999),
            'price_monthly' => '199.00',
            'price_yearly' => '1990.00',
            'features' => [
                'max_photos' => 15,
                'show_stats' => true,
                'highlighted_card' => true,
                'logo_and_cover' => true,
                'remove_competitor_ads' => false,
                'priority_support' => false,
            ],
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 1,
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => ['fr' => 'Gratuit', 'ar' => 'مجاني', 'en' => 'Free'],
            'price_monthly' => '0.00',
            'price_yearly' => '0.00',
            'features' => [
                'max_photos' => 3,
                'show_stats' => false,
                'highlighted_card' => false,
                'logo_and_cover' => false,
                'remove_competitor_ads' => false,
                'priority_support' => false,
            ],
            'is_default' => true,
            'sort_order' => 0,
        ]);
    }
}
