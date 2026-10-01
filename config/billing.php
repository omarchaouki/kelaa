<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment gateway
    |--------------------------------------------------------------------------
    |
    | manual: bank transfer or cash at an agency, receipt validated by an admin.
    | cmi: card payments through the Moroccan interbank gateway (skeleton only).
    |
    */

    'gateway' => env('PAYMENT_GATEWAY', 'manual'),

    'currency' => 'MAD',

    // Unpaid orders release their sponsored slot after this many hours.
    'pending_hold_hours' => (int) env('PAYMENT_PENDING_HOLD_HOURS', 72),

    // Days before expiry when reminders are sent.
    'reminder_days' => [7, 1],

    /*
    |--------------------------------------------------------------------------
    | Manual payment instructions (shown to owners)
    |--------------------------------------------------------------------------
    */

    'manual' => [
        'beneficiary' => env('PAYMENT_BENEFICIARY'),
        'bank_name' => env('PAYMENT_BANK_NAME'),
        // RIB: 24 digits.
        'rib' => env('PAYMENT_RIB'),
        'cash_agencies' => env('PAYMENT_CASH_AGENCIES', 'CashPlus, Wafacash'),
        // Name and phone/ID instructions for cash transfers.
        'cash_recipient' => env('PAYMENT_CASH_RECIPIENT'),
    ],

    'receipt_max_kb' => 5120,

    /*
    |--------------------------------------------------------------------------
    | CMI (Centre Monétique Interbancaire) - not active in v1
    |--------------------------------------------------------------------------
    */

    'cmi' => [
        'enabled' => (bool) env('CMI_ENABLED', false),
        'client_id' => env('CMI_CLIENT_ID'),
        'store_key' => env('CMI_STORE_KEY'),
        'gateway_url' => env('CMI_GATEWAY_URL', 'https://testpayment.cmi.co.ma/fim/est3Dgate'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoices
    |--------------------------------------------------------------------------
    |
    | Legal details of the company issuing invoices. PLACEHOLDERS until the
    | real company information is provided.
    |
    */

    'invoice' => [
        'prefix' => env('INVOICE_PREFIX', 'F'),
        'company_name' => env('INVOICE_COMPANY_NAME', 'À COMPLÉTER SARL'),
        'address' => env('INVOICE_COMPANY_ADDRESS', 'Adresse à compléter'),
        'ice' => env('INVOICE_COMPANY_ICE', '000000000000000'),
        'if' => env('INVOICE_COMPANY_IF'),
        'rc' => env('INVOICE_COMPANY_RC'),
        'patente' => env('INVOICE_COMPANY_PATENTE'),
        // Moroccan standard VAT is 20%. Set 0 if the company is exempt.
        'vat_rate' => (float) env('INVOICE_VAT_RATE', 20),
        // Prices entered in the admin include VAT.
        'prices_include_vat' => (bool) env('INVOICE_PRICES_INCLUDE_VAT', true),
    ],

];
