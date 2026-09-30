<?php

namespace Database\Factories;

use App\Enums\ClaimMethod;
use App\Enums\ClaimStatus;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessClaim>
 */
class BusinessClaimFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'method' => ClaimMethod::DocumentUpload,
            'notes' => null,
            'status' => ClaimStatus::Pending,
        ];
    }
}
