<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Neighborhood;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Reads database/seeders/data/city.json. Idempotent (matched by slug).
 */
class CitySeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/city.json');
        $data = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($data) || ! isset($data['city']['slug'])) {
            throw new RuntimeException("Invalid city file [{$path}].");
        }

        $city = City::query()->updateOrCreate(
            ['slug' => $data['city']['slug']],
            [
                'name' => $data['city']['name'],
                'center_latitude' => $data['city']['center']['latitude'],
                'center_longitude' => $data['city']['center']['longitude'],
                'default_zoom' => $data['city']['default_zoom'] ?? 13,
                'is_active' => true,
            ],
        );

        foreach ($data['neighborhoods'] ?? [] as $neighborhood) {
            Neighborhood::query()->updateOrCreate(
                ['city_id' => $city->getKey(), 'slug' => $neighborhood['slug']],
                [
                    'name' => $neighborhood['name'],
                    'latitude' => $neighborhood['center']['latitude'] ?? null,
                    'longitude' => $neighborhood['center']['longitude'] ?? null,
                    'is_active' => true,
                ],
            );
        }
    }
}
