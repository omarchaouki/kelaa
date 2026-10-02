<?php

use App\Enums\SearchSort;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\Plan;
use App\Services\Search\BusinessSearch;
use App\Services\Search\SearchFilters;
use App\Support\Geo\Coordinates;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->city = City::factory()->create();
    $this->otherCity = City::factory()->create();
    $this->group = Category::factory()->create(['slug' => 'maison']);
    $this->plumbers = Category::factory()->childOf($this->group)->create(['slug' => 'plombier', 'name' => ['fr' => 'Plombier', 'ar' => 'سباك', 'en' => 'Plumber']]);
    $this->mechanics = Category::factory()->create(['slug' => 'mecanicien', 'name' => ['fr' => 'Mécanicien', 'ar' => 'ميكانيكي', 'en' => 'Mechanic']]);
    $this->search = app(BusinessSearch::class);
});

function names($paginator): array
{
    return collect($paginator->items())->pluck('name')->all();
}

it('only lists published businesses of the requested city', function () {
    Business::factory()->for($this->city)->create(['name' => 'Visible']);
    Business::factory()->for($this->city)->pending()->create(['name' => 'Pending']);
    Business::factory()->for($this->city)->suspended()->create(['name' => 'Suspended']);
    Business::factory()->for($this->otherCity)->create(['name' => 'Elsewhere']);
    Business::factory()->for($this->city)->create(['name' => 'Deleted'])->delete();

    expect(names($this->search->search($this->city, new SearchFilters)))->toBe(['Visible']);
});

it('filters by category, including the children of a group', function () {
    Business::factory()->for($this->city)->inCategory($this->plumbers)->create(['name' => 'Plumber A']);
    Business::factory()->for($this->city)->inCategory($this->mechanics)->create(['name' => 'Garage B']);

    expect(names($this->search->search($this->city, new SearchFilters(categoryId: $this->plumbers->id))))->toBe(['Plumber A'])
        ->and(names($this->search->search($this->city, new SearchFilters(categoryId: $this->group->id))))->toBe(['Plumber A']);
});

it('filters by neighborhood, rating, price and verification', function () {
    $hood = Neighborhood::factory()->for($this->city)->create();

    Business::factory()->for($this->city)->create(['name' => 'Match', 'neighborhood_id' => $hood->id, 'price_range' => 2, 'is_verified' => true, 'rating_avg' => 4.6, 'rating_count' => 10]);
    Business::factory()->for($this->city)->create(['name' => 'Low rated', 'neighborhood_id' => $hood->id, 'price_range' => 2, 'is_verified' => true, 'rating_avg' => 3.1, 'rating_count' => 10]);
    Business::factory()->for($this->city)->create(['name' => 'Expensive', 'neighborhood_id' => $hood->id, 'price_range' => 4, 'is_verified' => true, 'rating_avg' => 4.8, 'rating_count' => 10]);
    Business::factory()->for($this->city)->create(['name' => 'Unverified', 'neighborhood_id' => $hood->id, 'price_range' => 2, 'is_verified' => false, 'rating_avg' => 4.8, 'rating_count' => 10]);
    Business::factory()->for($this->city)->create(['name' => 'Other hood', 'price_range' => 2, 'is_verified' => true, 'rating_avg' => 4.8, 'rating_count' => 10]);

    $filters = new SearchFilters(neighborhoodId: $hood->id, minRating: 4.5, priceRanges: [1, 2], verifiedOnly: true);

    expect(names($this->search->search($this->city, $filters)))->toBe(['Match']);
});

it('filters businesses open now', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 10:00', 'Africa/Casablanca'));

    $open = Business::factory()->for($this->city)->create(['name' => 'Open']);
    BusinessHour::query()->create(['business_id' => $open->id, 'day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '18:00']);
    $closed = Business::factory()->for($this->city)->create(['name' => 'Closed']);
    BusinessHour::query()->create(['business_id' => $closed->id, 'day_of_week' => 1, 'opens_at' => '14:00', 'closes_at' => '18:00']);

    expect(names($this->search->search($this->city, new SearchFilters(openNow: true))))->toBe(['Open']);
});

it('sorts by Bayesian score, reviews, newest and distance', function () {
    $here = new Coordinates(33.5731, -7.5898);

    Business::factory()->for($this->city)->at(33.6000, -7.5898)->create(['name' => 'A', 'bayesian_score' => 4.2, 'rating_count' => 80, 'published_at' => now()->subDays(30)]);
    Business::factory()->for($this->city)->at(33.5740, -7.5898)->create(['name' => 'B', 'bayesian_score' => 4.6, 'rating_count' => 3, 'published_at' => now()->subDays(1)]);
    Business::factory()->for($this->city)->at(33.5900, -7.5898)->create(['name' => 'C', 'bayesian_score' => 3.9, 'rating_count' => 20, 'published_at' => now()->subDays(10)]);

    expect(names($this->search->search($this->city, new SearchFilters(sort: SearchSort::Rating))))->toBe(['B', 'A', 'C'])
        ->and(names($this->search->search($this->city, new SearchFilters(sort: SearchSort::Reviews))))->toBe(['A', 'C', 'B'])
        ->and(names($this->search->search($this->city, new SearchFilters(sort: SearchSort::Newest))))->toBe(['B', 'C', 'A'])
        ->and(names($this->search->search($this->city, new SearchFilters(sort: SearchSort::Distance), $here)))->toBe(['B', 'C', 'A']);
});

it('falls back to relevance when sorting by distance without a location', function () {
    Business::factory()->for($this->city)->create(['name' => 'Top', 'bayesian_score' => 4.9]);
    Business::factory()->for($this->city)->create(['name' => 'Second', 'bayesian_score' => 3.0]);

    expect(names($this->search->search($this->city, new SearchFilters(sort: SearchSort::Distance))))->toBe(['Top', 'Second']);
});

it('limits results to a radius around the visitor', function () {
    $here = new Coordinates(33.5731, -7.5898);
    Business::factory()->for($this->city)->at(33.5776, -7.5898)->create(['name' => 'Near']);
    Business::factory()->for($this->city)->at(33.7000, -7.5898)->create(['name' => 'Far']);

    expect(names($this->search->search($this->city, new SearchFilters(radiusKm: 2), $here)))->toBe(['Near']);
});

it('searches text in names and category names in every language', function () {
    Business::factory()->for($this->city)->inCategory($this->plumbers)->create(['name' => 'Atelier Amine']);
    Business::factory()->for($this->city)->inCategory($this->mechanics)->create(['name' => 'Garage Atlas']);

    expect(names($this->search->search($this->city, new SearchFilters(query: 'Atlas'))))->toBe(['Garage Atlas'])
        ->and(names($this->search->search($this->city, new SearchFilters(query: 'سباك'))))->toBe(['Atelier Amine'])
        ->and(names($this->search->search($this->city, new SearchFilters(query: 'plumber'))))->toBe(['Atelier Amine'])
        ->and(names($this->search->search($this->city, new SearchFilters(query: 'nothing-matches'))))->toBe([]);
});

it('excludes businesses already shown in sponsored slots', function () {
    $sponsored = Business::factory()->for($this->city)->create(['name' => 'Sponsored']);
    Business::factory()->for($this->city)->create(['name' => 'Organic']);

    $results = $this->search->search($this->city, new SearchFilters, excludeIds: [$sponsored->id]);

    expect(names($results))->toBe(['Organic'])->and($results->total())->toBe(1);
});

it('ignores a paid plan when ordering results', function () {
    $paid = Plan::factory()->create();
    Business::factory()->for($this->city)->create(['name' => 'Better reviews', 'bayesian_score' => 4.5]);
    $premium = Business::factory()->for($this->city)->create(['name' => 'Premium', 'bayesian_score' => 3.5]);
    $premium->forceFill(['plan_id' => $paid->id, 'plan_expires_at' => now()->addMonth()])->save();

    // Every other relevance signal equal (the factory randomises them): only the rating differs.
    Business::query()->toBase()->update(['profile_completeness' => 80, 'is_verified' => false, 'last_activity_at' => now()]);

    expect(names($this->search->search($this->city, new SearchFilters(sort: SearchSort::Relevance))))->toBe(['Better reviews', 'Premium']);
});

it('paginates', function () {
    Business::factory()->count(25)->for($this->city)->create();

    $page2 = $this->search->search($this->city, new SearchFilters, perPage: 20, page: 2);

    expect($page2->total())->toBe(25)->and($page2->items())->toHaveCount(5);
});
