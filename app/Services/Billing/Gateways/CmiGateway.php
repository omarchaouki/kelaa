<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\PaymentGateway;
use App\Models\Payment;
use Illuminate\Http\Request;
use LogicException;

/**
 * CMI card payments: SKELETON ONLY (spec §8, "do not implement the full
 * integration yet"). Routes and config exist; nothing is accepted.
 *
 * To finish the integration:
 * 1. instructions(): build the signed form (clientid, oid = reference, amount,
 *    currency 504, okUrl, failUrl, callbackUrl, rnd, hashAlgorithm=ver3,
 *    HASH = base64(sha512(params escaped and joined with "|" + store key))).
 * 2. handleCallback(): recompute HASH from the posted fields with the store
 *    key, compare with hash_equals, check ProcReturnCode=00 and that the
 *    amount and oid match a pending payment, then validate it through
 *    BillingGate::asSystem() and answer "ACTION=POSTAUTH".
 * 3. Never trust okUrl/failUrl redirects: only the verified callback counts.
 */
class CmiGateway implements PaymentGateway
{
    public function instructions(Payment $payment): array
    {
        throw new LogicException('CMI payments are not available yet.');
    }

    public function handleCallback(Request $request): ?Payment
    {
        return null;
    }

    public static function enabled(): bool
    {
        return config('billing.cmi.enabled') === true
            && filled(config('billing.cmi.client_id'))
            && filled(config('billing.cmi.store_key'));
    }
}
