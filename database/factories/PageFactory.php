<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'title' => ['fr' => fake()->sentence(3), 'ar' => fake()->sentence(3), 'en' => fake()->sentence(3)],
            'body' => ['fr' => fake()->paragraphs(3, true), 'ar' => fake()->paragraphs(3, true), 'en' => fake()->paragraphs(3, true)],
            'is_published' => true,
        ];
    }
}
