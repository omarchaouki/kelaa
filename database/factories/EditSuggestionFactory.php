<?php

namespace Database\Factories;

use App\Enums\EditSuggestionStatus;
use App\Models\Business;
use App\Models\EditSuggestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditSuggestion>
 */
class EditSuggestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'changes' => ['phone' => '+2125'.fake()->numerify('########')],
            'comment' => 'Le numéro a changé.',
            'status' => EditSuggestionStatus::Pending,
        ];
    }
}
