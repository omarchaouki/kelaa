<?php

return [

    // Business submissions per user per 24 hours.
    'submissions_per_day' => (int) env('OWNERS_SUBMISSIONS_PER_DAY', 3),

    // Edit suggestions per user per 24 hours.
    'suggestions_per_day' => (int) env('OWNERS_SUGGESTIONS_PER_DAY', 10),

    // Claim attempts (OTP sends or document submissions) per user per 24 hours.
    'claims_per_day' => (int) env('OWNERS_CLAIMS_PER_DAY', 3),

    // Duplicate detection: same name within this radius (meters) and similarity (0-100).
    'duplicate_radius_m' => 200,
    'duplicate_name_similarity' => 80,

    // Claim documents (registre de commerce, patente, CIN).
    'claim_documents_max' => 3,
    'claim_document_kb' => 5120,

    // Plan used when a business has no active paid plan and no default plan exists.
    'fallback_features' => [
        'max_photos' => 3,
        'show_stats' => false,
        'highlighted_card' => false,
        'logo_and_cover' => false,
        'remove_competitor_ads' => false,
        'priority_support' => false,
    ],

];
