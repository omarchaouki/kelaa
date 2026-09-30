<?php

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reportable_type' => 'review',
            'reportable_id' => Review::factory(),
            'reporter_id' => User::factory(),
            'reason' => fake()->randomElement(ReportReason::cases()),
            'details' => fake()->optional()->sentence(),
            'status' => ReportStatus::Open,
        ];
    }
}
