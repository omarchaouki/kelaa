<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+212'.fake()->randomElement(['6', '7']).fake()->unique()->numerify('########'),
            'phone_verified_at' => now()->subDays(fake()->numberBetween(2, 400)),
            'email' => null,
            'password' => null,
            'locale' => 'fr',
            'remember_token' => Str::random(10),
            'created_at' => now()->subDays(fake()->numberBetween(2, 400)),
        ];
    }

    public function unverifiedPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
        ]);
    }

    public function newAccount(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subHours(2),
            'phone_verified_at' => now()->subHours(2),
        ]);
    }

    public function withEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
        ]);
    }

    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'banned_at' => now(),
            'ban_reason' => 'spam',
        ]);
    }

    public function withRole(Role $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role->value));
    }

    public function admin(): static
    {
        return $this->withRole(Role::Admin);
    }

    public function moderator(): static
    {
        return $this->withRole(Role::Moderator);
    }

    public function businessOwner(): static
    {
        return $this->withRole(Role::BusinessOwner);
    }
}
