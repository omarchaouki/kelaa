<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\PharmacyDuty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PharmacyDuty>
 */
class PharmacyDutyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = now()->startOfDay();

        return [
            'business_id' => Business::factory(),
            'date' => $date,
            // Evening to next morning.
            'starts_at' => $date->copy()->setTime(20, 0),
            'ends_at' => $date->copy()->addDay()->setTime(9, 0),
        ];
    }
}
