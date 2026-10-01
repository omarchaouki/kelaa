<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\PaymentGateway;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Bank transfer or cash at an agency (CashPlus, Wafacash). The owner uploads
 * a receipt, an admin validates it in the panel.
 */
class ManualGateway implements PaymentGateway
{
    public function instructions(Payment $payment): array
    {
        $manual = config('billing.manual');

        return [
            'type' => 'manual',
            'reference' => $payment->reference,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'beneficiary' => $manual['beneficiary'],
            'bank_name' => $manual['bank_name'],
            'rib' => $manual['rib'],
            'cash_agencies' => $manual['cash_agencies'],
            'cash_recipient' => $manual['cash_recipient'],
            'expires_at' => $payment->expires_at,
        ];
    }

    public function handleCallback(Request $request): ?Payment
    {
        // Manual payments have no callback.
        return null;
    }
}
