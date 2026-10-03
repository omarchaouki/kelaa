<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Review;
use App\Models\SearchSynonym;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevDataSeeder;
use Spatie\Permission\Models\Role;

it('seeds reference data idempotently', function () {
    $this->seed(DatabaseSeeder::class);

    $counts = fn () => [
        City::count(), Neighborhood::count(), Category::count(), Plan::count(),
        Page::count(), SearchSynonym::count(), Role::count(),
    ];

    $first = $counts();
    $this->seed(DatabaseSeeder::class);

    expect($counts())->toBe($first)
        ->and(Role::count())->toBe(4)
        ->and(Plan::count())->toBe(3)
        ->and(City::count())->toBe(1)
        ->and(Neighborhood::count())->toBeGreaterThan(0);
});

it('seeds the category tree from the spec in three languages', function () {
    $this->seed(DatabaseSeeder::class);

    $groups = Category::query()->roots()->pluck('slug');
    expect($groups)->toContain('urgences', 'maison', 'auto-et-moto', 'transport', 'livraison', 'food', 'beaute-et-bien-etre', 'sante', 'services');

    foreach (Category::all() as $category) {
        foreach (['fr', 'ar', 'en'] as $locale) {
            expect($category->getTranslation('name', $locale, false))->not->toBeEmpty("{$category->slug} misses {$locale}");
        }
    }

    expect(Category::query()->where('slug', 'pharmacie-de-garde')->sole()->is_emergency)->toBeTrue()
        ->and(Category::query()->where('slug', 'plombier')->sole()->is_emergency)->toBeFalse();
});

it('seeds only the real places of El Kelaa, once, without fake data', function () {
    $this->seed(DatabaseSeeder::class);

    $places = json_decode(file_get_contents(database_path('seeders/data/businesses.json')), true)['businesses'];

    expect(Business::count())->toBe(count($places))
        ->and(User::count())->toBe(0)
        ->and(Review::count())->toBe(0)
        ->and(Business::query()->where('slug', 'pharmacie-imlil')->sole()->categories->pluck('slug')->all())->toBe(['pharmacie-de-garde'])
        ->and(Business::query()->whereDoesntHave('categories')->count())->toBe(0);

    $this->seed(DatabaseSeeder::class);

    expect(Business::count())->toBe(count($places));
});

it('seeds every legal page in three languages without placeholders', function () {
    $this->seed(DatabaseSeeder::class);

    foreach (Page::all() as $page) {
        foreach (['fr', 'ar', 'en'] as $locale) {
            $body = (string) $page->getTranslation('body', $locale, false);

            expect($body)->not->toBeEmpty("{$page->slug} misses {$locale}")
                ->and($body)->not->toContain('[');
        }
    }
});

it('refuses to run the dev data seeder in production', function () {
    app()->detectEnvironment(fn () => 'production');

    app(DevDataSeeder::class)->run();
})->throws(RuntimeException::class, 'production');

it('never overwrites plan prices edited in the admin', function () {
    $this->seed(DatabaseSeeder::class);
    Plan::query()->where('slug', 'pro')->update(['price_monthly' => 999]);

    $this->seed(DatabaseSeeder::class);

    expect((float) Plan::query()->where('slug', 'pro')->value('price_monthly'))->toBe(999.0);
});
