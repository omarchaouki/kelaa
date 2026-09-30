<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\Page;
use App\Models\Plan;
use App\Models\SearchSynonym;
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

it('does not seed fake data outside the local environment', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Business::count())->toBe(0);
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
