<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bayesian average
    |--------------------------------------------------------------------------
    |
    | score = (v / (v + m)) * R + (m / (v + m)) * C
    |
    | "m" is the number of "virtual" reviews at the category/city average C
    | that every business starts with. Higher = more reviews needed to move.
    |
    */

    'bayesian_m' => (float) env('RANKING_BAYESIAN_M', 5),

    // Used for C when a category/city has no published review yet.
    'fallback_average' => (float) env('RANKING_FALLBACK_AVERAGE', 3.5),

    /*
    |--------------------------------------------------------------------------
    | Relevance weights
    |--------------------------------------------------------------------------
    |
    | Each signal is normalised to 0..1 before weighting. Paid plans and
    | sponsored placements are deliberately NOT a signal.
    |
    */

    'weights' => [
        'bayesian' => (float) env('RANKING_WEIGHT_BAYESIAN', 0.40),
        'text' => (float) env('RANKING_WEIGHT_TEXT', 0.25),
        'distance' => (float) env('RANKING_WEIGHT_DISTANCE', 0.15),
        'completeness' => (float) env('RANKING_WEIGHT_COMPLETENESS', 0.08),
        'recency' => (float) env('RANKING_WEIGHT_RECENCY', 0.07),
        'verified' => (float) env('RANKING_WEIGHT_VERIFIED', 0.05),
    ],

    // Distance (km) at which the distance signal reaches 0.
    'distance_horizon_km' => (float) env('RANKING_DISTANCE_HORIZON_KM', 10),

    // Days of inactivity after which the recency signal reaches 0.
    'recency_horizon_days' => (int) env('RANKING_RECENCY_HORIZON_DAYS', 365),

];
