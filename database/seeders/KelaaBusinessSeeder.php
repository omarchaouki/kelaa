<?php

namespace Database\Seeders;

use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Real places of El Kelaa des Sraghna from OpenStreetMap (data/businesses.json,
 * © OpenStreetMap contributors, ODbL). Only what OSM records is imported:
 * name, position, street and contacts when present; nothing is invented.
 * Owners complete their listing by claiming it. Idempotent: existing slugs
 * are left untouched.
 */
class KelaaBusinessSeeder extends Seeder
{
    public function run(): void
    {
        $city = City::query()->where('slug', 'el-kelaa')->first();

        if ($city === null) {
            return;
        }

        /** @var list<array{osm: string, name: string, category: string, latitude: float, longitude: float, address: ?string, phone: ?string, website: ?string}> $places */
        $places = json_decode((string) file_get_contents(__DIR__.'/data/businesses.json'), true, flags: JSON_THROW_ON_ERROR)['businesses'];
        $categories = Category::query()->whereIn('slug', array_unique(array_column($places, 'category')))->pluck('id', 'slug');

        // Slugs use the Latin part of the name ("Pharmacie Imlil صيدلية إمليل"
        // → pharmacie-imlil). Arabic-only names fall back to the category, and
        // names shared by several places (fuel brands…) get their OSM id.
        $latin = fn (string $name): string => Str::limit(Str::slug(preg_replace(['/\p{Arabic}+/u', '/[’\']/u'], ' ', $name)), 70, '');
        $bases = array_map(fn (array $place): string => $latin($place['name']) ?: $place['category'], $places);
        $shared = array_keys(array_filter(array_count_values($bases), fn (int $count) => $count > 1));

        DB::transaction(function () use ($places, $bases, $shared, $latin, $categories, $city): void {
            foreach ($places as $i => $place) {
                $categoryId = $categories[$place['category']] ?? null;

                if ($categoryId === null) {
                    continue;
                }

                $slug = in_array($bases[$i], $shared, true) || $latin($place['name']) === ''
                    ? $bases[$i].'-'.Str::afterLast($place['osm'], '/')
                    : $bases[$i];

                if (Business::query()->withTrashed()->where('city_id', $city->id)->where('slug', $slug)->exists()) {
                    continue;
                }

                $business = new Business;
                $business->forceFill([
                    'city_id' => $city->id,
                    'name' => $place['name'],
                    'slug' => $slug,
                    'address' => $place['address'],
                    'latitude' => $place['latitude'],
                    'longitude' => $place['longitude'],
                    'phone' => $place['phone'],
                    'website' => $place['website'],
                    'status' => BusinessStatus::Published,
                    'published_at' => now(),
                    'last_activity_at' => now(),
                ])->save();

                $business->categories()->attach($categoryId, ['is_primary' => true]);
            }
        });
    }
}
