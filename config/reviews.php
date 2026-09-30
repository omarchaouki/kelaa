<?php

return [

    // Optional text: when given, at least this many characters (trimmed, multibyte-aware).
    'min_body_length' => (int) env('REVIEWS_MIN_BODY_LENGTH', 20),

    'max_body_length' => (int) env('REVIEWS_MAX_BODY_LENGTH', 2000),

    'max_photos' => 5,

    // Photo upload size in kilobytes.
    'max_photo_kb' => (int) env('REVIEWS_MAX_PHOTO_KB', 5120),

    // New reviews per user per rolling 24 hours.
    'daily_limit' => (int) env('REVIEWS_DAILY_LIMIT', 5),

    /*
    |--------------------------------------------------------------------------
    | Abuse rules: a review matching any rule goes to moderation
    |--------------------------------------------------------------------------
    */

    'abuse' => [
        // Accounts younger than this are moderated.
        'new_account_hours' => (int) env('REVIEWS_NEW_ACCOUNT_HOURS', 24),
        // More than this many reviews on one business within the window is a burst.
        'burst_count' => (int) env('REVIEWS_BURST_COUNT', 5),
        'burst_hours' => (int) env('REVIEWS_BURST_HOURS', 24),
    ],

    // Reports per user per hour.
    'reports_per_hour' => (int) env('REPORTS_PER_HOUR', 10),

    // Replies publish immediately unless they contain a banned word.
    'replies_premoderated' => (bool) env('REVIEW_REPLIES_PREMODERATED', false),

];
