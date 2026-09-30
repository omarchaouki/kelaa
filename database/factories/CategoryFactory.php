<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'parent_id' => null,
            'name' => ['fr' => $name, 'ar' => $name, 'en' => $name],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'icon' => 'wrench',
            'sort_order' => fake()->numberBetween(0, 100),
            'is_emergency' => false,
            'is_active' => true,
        ];
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn (array $attributes) => ['parent_id' => $parent->getKey()]);
    }

    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => ['is_emergency' => true]);
    }
}
