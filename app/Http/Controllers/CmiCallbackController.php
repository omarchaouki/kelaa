<?php

namespace App\Http\Controllers;

use App\Services\Billing\Gateways\CmiGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CMI routes (callback, ok, fail). Inactive until the integration is finished:
 * they answer 404 so nothing can be probed or replayed.
 */
class CmiCallbackController extends Controller
{
    public function callback(Request $request, CmiGateway $gateway): Response
    {
        abort_unless(CmiGateway::enabled(), 404);

        $payment = $gateway->handleCallback($request);

        return response($payment === null ? 'APPROVED=NO' : 'ACTION=POSTAUTH', 200, ['Content-Type' => 'text/plain']);
    }

    public function return(): Response
    {
        abort_unless(CmiGateway::enabled(), 404);

        // The browser return never confirms a payment; only the verified callback does.
        return response()->noContent();
    }
}
