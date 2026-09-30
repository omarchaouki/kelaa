<?php

use App\Enums\BusinessStatus;
use App\Enums\PlacementStatus;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\SponsoredPlacement;
use App\Services\Sponsoring\SponsoredSlots;

beforeEach(function () {
    $this->city = City::factory()->create();
    $this->category = Category::factory()->create();
    $this->slots = app(SponsoredSlots::class);
});

function placement(City $city, Category $category, array $attributes = [], array $business = []): SponsoredPlacement
{
    return SponsoredPlacement::factory()->create([
        'business_id' => Business::factory()->for($city)->create($business)->id,
        'city_id' => $city->id,
        'category_id' => $category->id,
        ...$attributes,
    ]);
}

it('returns running placements ordered by position, at most three', function () {
    $third = placement($this->city, $this->category, ['position' => 3]);
    $first = placement($this->city, $this->category, ['position' => 1]);
    $second = placement($this->city, $this->category, ['position' => 2]);
    placement($this->city, $this->category, ['position' => 3]); // oversold: ignored

    expect($this->slots->for($this->city, $this->category)->pluck('id')->all())->toBe([$first->id, $second->id, $third->id]);
});

it('ignores expired, future, cancelled and unpublished placements', function () {
    placement($this->city, $this->category, ['starts_at' => now()->subDays(10), 'ends_at' => now()->subDay()]);
    placement($this->city, $this->category, ['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(8)]);
    placement($this->city, $this->category, ['status' => PlacementStatus::Cancelled]);
    placement($this->city, $this->category, [], ['status' => BusinessStatus::Suspended]);

    expect($this->slots->for($this->city, $this->category))->toBeEmpty();
});

it('shows neighborhood placements first, then city-wide ones', function () {
    $hood = Neighborhood::factory()->for($this->city)->create();
    $otherHood = Neighborhood::factory()->for($this->city)->create();

    $cityWide = placement($this->city, $this->category, ['position' => 1]);
    $targeted = placement($this->city, $this->category, ['position' => 2, 'neighborhood_id' => $hood->id]);
    placement($this->city, $this->category, ['position' => 1, 'neighborhood_id' => $otherHood->id]);

    expect($this->slots->for($this->city, $this->category, $hood)->pluck('id')->all())->toBe([$targeted->id, $cityWide->id])
        ->and($this->slots->for($this->city, $this->category)->pluck('id')->all())->toBe([$cityWide->id]);
});

it('never shows the same business twice', function () {
    $business = Business::factory()->for($this->city)->create();
    SponsoredPlacement::factory()->count(2)->sequence(['position' => 1], ['position' => 2])->create([
        'business_id' => $business->id, 'city_id' => $this->city->id, 'category_id' => $this->category->id,
    ]);

    expect($this->slots->for($this->city, $this->category))->toHaveCount(1);
});
