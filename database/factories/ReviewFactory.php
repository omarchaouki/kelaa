<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Business;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bodies = [
            'Travail propre et rapide, je recommande vivement.',
            'Très professionnel, prix raisonnable et ponctuel.',
            'Service correct mais un peu cher pour la prestation.',
            'Accueil chaleureux, je reviendrai sans hésiter.',
            'خدمة ممتازة وثمن مناسب، أنصح به.',
            'Rendez-vous non respecté, déçu du service.',
            'Great service, very friendly and fast.',
        ];

        $publishedAt = now()->subDays(fake()->numberBetween(0, 365));

        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->randomElement([1, 2, 3, 4, 4, 4, 5, 5, 5, 5]),
            'body' => fake()->optional(0.8)->randomElement($bodies),
            'locale' => 'fr',
            'status' => ReviewStatus::Published,
            'published_at' => $publishedAt,
            'created_at' => $publishedAt,
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'device_hash' => hash('sha256', fake()->uuid()),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::Pending,
            'published_at' => null,
        ]);
    }

    public function rating(int $rating): static
    {
        return $this->state(fn (array $attributes) => ['rating' => $rating]);
    }
}
