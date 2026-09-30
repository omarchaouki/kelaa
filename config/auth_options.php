<?php

return [

    // Phone number + OTP is always enabled. These are optional extra methods.
    'email_password' => (bool) env('AUTH_EMAIL_PASSWORD_ENABLED', false),

    'google' => (bool) env('AUTH_GOOGLE_ENABLED', false),

];
