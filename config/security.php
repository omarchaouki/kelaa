<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Scripts need a per-request nonce (Livewire, Filament and Vite add it
    | automatically). 'unsafe-eval' is required by Alpine.js' standard build,
    | used by Livewire and Filament. Inline styles stay allowed because
    | Filament and Leaflet set style attributes.
    |
    */

    'csp' => [
        'enabled' => (bool) env('CSP_ENABLED', true),

        // Report violations without blocking (useful when adding a new third party).
        'report_only' => (bool) env('CSP_REPORT_ONLY', false),

        // Extra image hosts (map tiles, CDN). The map tile host is added automatically.
        'img_hosts' => array_filter(explode(',', (string) env('CSP_IMG_HOSTS', ''))),

        'report_uri' => env('CSP_REPORT_URI'),

        // Filament's own inline scripts (no nonce support), allowed by exact hash on
        // admin pages only. tests/Feature/Security/AdminInlineScriptsTest.php lists
        // the new hashes when a Filament update changes them.
        'admin_script_hashes' => [
            "'sha256-U1bkbkwEe1mD+tUU83n0bTj/yfxW/ZR1XA7I/KciRwU='", // dark mode loader (layout head)
            "'sha256-0mqBbIWwNzdgIFPFU9BM5l9RnzSn/EbrlFBGLZHGf5Y='", // loadDarkMode() call (layout body)
            "'sha256-CUO614iJ1yQTie8j253djmvmNcad8Y2m0MoYGZs+JQo='", // sidebar collapsed groups
        ],
    ],

    // Reverse proxy / load balancer IPs whose X-Forwarded-* headers are trusted
    // (comma separated). Leave empty when PHP-FPM is reached directly by Nginx.
    'trusted_proxies' => array_filter(explode(',', (string) env('TRUSTED_PROXIES', ''))),

    // Strict-Transport-Security, only sent over HTTPS.
    'hsts_max_age' => (int) env('HSTS_MAX_AGE', 31536000),

    /*
    |--------------------------------------------------------------------------
    | Rate limits (per minute)
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        // Every Livewire interaction (search typing, filters, pagination...).
        'livewire' => (int) env('RATE_LIMIT_LIVEWIRE', 120),
        // Contact button clicks / profile views logged per IP.
        'contact_events' => (int) env('RATE_LIMIT_CONTACT_EVENTS', 60),
    ],

];
