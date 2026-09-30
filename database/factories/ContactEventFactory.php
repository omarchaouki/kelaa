<?php

namespace Database\Factories;

use App\Enums\ContactEventSource;
use App\Enums\ContactEventType;
use App\Models\Business;
use App\Models\ContactEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactEvent>
 */
class ContactEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'type' => fake()->randomElement(ContactEventType::cases()),
            'source' => fake()->randomElement(ContactEventSource::cases()),
            'session_hash' => hash('sha256', fake()->uuid()),
            'created_at' => now()->subMinutes(fake()->numberBetween(0, 60 * 24 * 30)),
        ];
    }
}
