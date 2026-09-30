<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | Every public URL is prefixed with one of these locales. The first one is
    | the default. "rtl" locales switch the layout direction.
    |
    */

    'locales' => [
        'fr' => ['name' => 'Français', 'native' => 'Français', 'dir' => 'ltr', 'regional' => 'fr_MA'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'dir' => 'rtl', 'regional' => 'ar_MA'],
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr', 'regional' => 'en_US'],
    ],

    'default_locale' => 'fr',

    /*
    |--------------------------------------------------------------------------
    | City
    |--------------------------------------------------------------------------
    |
    | Slug of the city served at launch. The data model supports many cities,
    | this only decides where "/" and "/{locale}" redirect to.
    |
    */

    'default_city' => env('DIRECTORY_DEFAULT_CITY', 'ma-ville'),

    'timezone' => 'Africa/Casablanca',

    'currency' => 'MAD',

    /*
    |--------------------------------------------------------------------------
    | Legal
    |--------------------------------------------------------------------------
    |
    | CNDP declaration / authorization number shown in the footer (Law 09-08).
    |
    */

    'cndp_number' => env('CNDP_NUMBER'),

    /*
    |--------------------------------------------------------------------------
    | Maps
    |--------------------------------------------------------------------------
    |
    | Public OSM tile servers forbid heavy production use: point these at a
    | commercial provider (MapTiler, Stadia, ...) before launch.
    |
    */

    'maps' => [
        'tile_url' => env('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'attribution' => env('MAP_TILE_ATTRIBUTION', '&copy; OpenStreetMap contributors'),
        'max_zoom' => (int) env('MAP_MAX_ZOOM', 19),
    ],

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

    'category_max_depth' => 2,

];
