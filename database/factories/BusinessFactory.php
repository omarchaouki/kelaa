<?php

namespace Database\Factories;

use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\Category;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prefixes = ['Atelier', 'Garage', 'Chez', 'Pharmacie', 'Café', 'Snack', 'Boulangerie', 'Salon', 'Dar', 'Espace', 'Clinique', 'Cabinet'];
        $names = ['Amine', 'Youssef', 'Fatima', 'Hassan', 'Nadia', 'Rachid', 'Khadija', 'Omar', 'Salma', 'Karim', 'Al Amal', 'Annour', 'Assalam', 'Al Baraka', 'Atlas'];
        $name = fake()->randomElement($prefixes).' '.fake()->randomElement($names);
        $phone = '+2125'.fake()->numerify('########');

        return [
            'city_id' => City::factory(),
            'neighborhood_id' => null,
            'owner_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => ['fr' => fake()->realText(160), 'ar' => null, 'en' => null],
            'address' => fake()->numberBetween(1, 200).', '.fake()->randomElement(['Rue', 'Avenue', 'Boulevard']).' '.fake()->lastName(),
            'latitude' => 33.5731 + fake()->randomFloat(5, -0.05, 0.05),
            'longitude' => -7.5898 + fake()->randomFloat(5, -0.05, 0.05),
            'phone' => $phone,
            'whatsapp' => '+2126'.fake()->numerify('########'),
            'email' => fake()->optional(0.3)->safeEmail(),
            'website' => fake()->optional(0.2)->url(),
            'social_links' => null,
            'price_range' => fake()->optional(0.7)->numberBetween(1, 4),
            'is_always_open' => false,
            'status' => BusinessStatus::Published,
            'is_verified' => fake()->boolean(20),
            'is_claimed' => false,
            'profile_completeness' => fake()->numberBetween(30, 100),
            'last_activity_at' => now()->subDays(fake()->numberBetween(0, 200)),
            'published_at' => now()->subDays(fake()->numberBetween(1, 400)),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BusinessStatus::Pending,
            'published_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['status' => BusinessStatus::Suspended]);
    }

    public function at(float $latitude, float $longitude): static
    {
        return $this->state(fn (array $attributes) => [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function inCategory(Category $category, bool $primary = true): static
    {
        return $this->afterCreating(fn (Business $business) => $business->categories()->attach($category, ['is_primary' => $primary]));
    }

    /**
     * Monday-Saturday split hours (09:00-13:00, 15:00-20:00), closed on Sunday.
     */
    public function withRegularHours(): static
    {
        return $this->afterCreating(function (Business $business): void {
            foreach (range(1, 6) as $day) {
                BusinessHour::factory()->for($business)->createMany([
                    ['day_of_week' => $day, 'opens_at' => '09:00', 'closes_at' => '13:00'],
                    ['day_of_week' => $day, 'opens_at' => '15:00', 'closes_at' => '20:00'],
                ]);
            }

            BusinessHour::factory()->for($business)->closed()->create(['day_of_week' => 7]);
        });
    }
}
