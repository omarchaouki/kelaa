<?php

return [

    // Phone number + SMS code. When off (no SMS provider yet), accounts use
    // phone number + password and numbers are not verified by SMS.
    'sms_verification' => (bool) env('SMS_VERIFICATION_ENABLED', false),

    // Optional extra methods.
    'email_password' => (bool) env('AUTH_EMAIL_PASSWORD_ENABLED', false),

    'google' => (bool) env('AUTH_GOOGLE_ENABLED', false),

];
