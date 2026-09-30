<?php

namespace Database\Factories;

use App\Enums\ReplyStatus;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewReply>
 */
class ReviewReplyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'user_id' => User::factory(),
            'body' => fake()->randomElement([
                'Merci beaucoup pour votre retour, à bientôt !',
                'Nous sommes désolés pour ce désagrément, contactez-nous sur WhatsApp.',
                'شكرا على ثقتكم',
            ]),
            'status' => ReplyStatus::Published,
        ];
    }
}
