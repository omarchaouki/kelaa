<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | log: writes the code to the application log (development only)
    | sms: SMS provider (to be configured)
    | whatsapp: WhatsApp Business provider (to be configured)
    |
    */

    'driver' => env('OTP_DRIVER', 'log'),

    'length' => 6,

    'ttl_minutes' => (int) env('OTP_TTL_MINUTES', 10),

    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    'rate_limits' => [
        // Requests for a code, per phone number.
        'per_phone' => ['attempts' => (int) env('OTP_LIMIT_PER_PHONE', 3), 'decay_minutes' => 15],
        'per_phone_daily' => ['attempts' => (int) env('OTP_LIMIT_PER_PHONE_DAILY', 10), 'decay_minutes' => 1440],
        // Requests for a code, per IP address.
        'per_ip' => ['attempts' => (int) env('OTP_LIMIT_PER_IP', 10), 'decay_minutes' => 60],
        // Verification attempts, per phone number.
        'verify_per_phone' => ['attempts' => (int) env('OTP_VERIFY_LIMIT_PER_PHONE', 10), 'decay_minutes' => 15],
    ],

    'drivers' => [
        'sms' => [
            'provider' => env('OTP_SMS_PROVIDER'),
            'api_key' => env('OTP_SMS_API_KEY'),
            'sender' => env('OTP_SMS_SENDER'),
        ],
        'whatsapp' => [
            'provider' => env('OTP_WHATSAPP_PROVIDER'),
            'api_key' => env('OTP_WHATSAPP_API_KEY'),
            'phone_number_id' => env('OTP_WHATSAPP_PHONE_NUMBER_ID'),
            'template' => env('OTP_WHATSAPP_TEMPLATE'),
        ],
    ],

];
