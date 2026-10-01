<?php

namespace App\Services\Billing;

use App\Enums\PaymentPurpose;
use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Localizable;

/**
 * Issues one invoice per validated payment. Numbers are sequential per year
 * without gaps (row lock on invoice_sequences), and the client and company
 * details are copied at issue time: an invoice never changes afterwards.
 */
class InvoiceIssuer
{
    use Localizable;

    public function __construct(
        private readonly Pricing $pricing,
    ) {}

    public function issue(Payment $payment): Invoice
    {
        $payment->loadMissing(['business', 'subscription.plan', 'sponsoredPlacement.category']);

        return DB::transaction(function () use ($payment): Invoice {
            $issuedAt = now();
            $year = (int) $issuedAt->format('Y');
            $sequence = $this->nextSequence($year);
            $vat = $this->pricing->vat((string) $payment->amount);
            $business = $payment->business;

            return Invoice::query()->create([
                'number' => sprintf('%s-%d-%05d', config('billing.invoice.prefix'), $year, $sequence),
                'year' => $year,
                'sequence' => $sequence,
                'business_id' => $business->id,
                'client_name' => $business->legal_name ?: $business->name,
                'client_ice' => $business->ice,
                'client_address' => $business->legal_address ?: $business->address,
                'lines' => [[
                    // Invoices are always issued in French, whatever the admin's language.
                    'description' => $this->withLocale('fr', fn () => $this->description($payment)),
                    'quantity' => 1,
                    'unit_price' => $vat['subtotal'],
                    'total' => $vat['subtotal'],
                ]],
                'subtotal' => $vat['subtotal'],
                'tax_rate' => $vat['tax_rate'],
                'tax_amount' => $vat['tax_amount'],
                'total' => $vat['total'],
                'currency' => $payment->currency,
                'issued_at' => $issuedAt,
            ]);
        });
    }

    /**
     * Renders (once) and returns the path of the PDF, stored on the private disk.
     */
    public function pdfPath(Invoice $invoice): string
    {
        $existing = $invoice->getFirstMedia('pdf');

        if ($existing !== null) {
            return $existing->getPath();
        }

        $html = $this->withLocale('fr', fn () => view('invoices.pdf', [
            'invoice' => $invoice,
            'company' => config('billing.invoice'),
        ])->render());

        $tmp = tempnam(sys_get_temp_dir(), 'inv').'.pdf';
        Pdf::loadHTML($html)->setPaper('a4')->setOption('isRemoteEnabled', false)->save($tmp);

        return $invoice->addMedia($tmp)
            ->usingFileName($invoice->number.'.pdf')
            ->toMediaCollection('pdf')
            ->getPath();
    }

    private function nextSequence(int $year): int
    {
        DB::table('invoice_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);

        $current = (int) DB::table('invoice_sequences')->where('year', $year)->lockForUpdate()->value('last_number');
        DB::table('invoice_sequences')->where('year', $year)->update(['last_number' => $current + 1]);

        return $current + 1;
    }

    private function description(Payment $payment): string
    {
        if ($payment->purpose === PaymentPurpose::Subscription && $payment->subscription !== null) {
            $subscription = $payment->subscription;

            return __('Abonnement :plan (:period) – :business', [
                'plan' => $subscription->plan->getTranslation('name', 'fr'),
                'period' => $subscription->billing_period->getLabel(),
                'business' => $payment->business->name,
            ]);
        }

        $placement = $payment->sponsoredPlacement;

        return $placement === null
            ? __('Prestation – :business', ['business' => $payment->business->name])
            : __('Emplacement sponsorisé position :position – :category – du :from au :to', [
                'position' => $placement->position,
                'category' => $placement->category->getTranslation('name', 'fr'),
                'from' => $placement->starts_at->format('d/m/Y'),
                'to' => $placement->ends_at->format('d/m/Y'),
            ]);
    }
}
