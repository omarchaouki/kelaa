<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Business;
use App\Models\Invoice;
use App\Services\Billing\InvoiceIssuer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoiceController extends Controller
{
    /**
     * Owner download: the invoice must belong to a business the user manages.
     */
    public function owner(Business $business, Invoice $invoice, InvoiceIssuer $issuer): BinaryFileResponse
    {
        Gate::authorize('manage', $business);
        abort_unless($invoice->business_id === $business->id, 404);

        return $this->download($invoice, $issuer);
    }

    /**
     * Staff download (admins with payment rights only).
     */
    public function admin(Request $request, Invoice $invoice, InvoiceIssuer $issuer): BinaryFileResponse
    {
        abort_unless($request->user()?->can(Permission::ManagePayments->value) && ! $request->user()->isBanned(), 404);

        return $this->download($invoice, $issuer);
    }

    private function download(Invoice $invoice, InvoiceIssuer $issuer): BinaryFileResponse
    {
        return response()->download($issuer->pdfPath($invoice), $invoice->number.'.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
