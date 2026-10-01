<?php

namespace App\Contracts;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * What the owner sees after ordering: bank details, a redirect form, etc.
     *
     * @return array<string, mixed>
     */
    public function instructions(Payment $payment): array;

    /**
     * Handles the gateway's server-to-server notification. Must verify its
     * authenticity before changing anything.
     */
    public function handleCallback(Request $request): ?Payment;
}
