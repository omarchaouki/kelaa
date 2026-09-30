<?php

use App\Models\Business;
use App\Models\City;
use App\Support\Geo\Coordinates;

beforeEach(function () {
    $this->city = City::factory()->create();
    $this->center = new Coordinates(33.5731, -7.5898);

    // Roughly 0.5 km, 3 km and 20 km away from the center.
    $this->near = Business::factory()->for($this->city)->at(33.5776, -7.5898)->create(['name' => 'Near']);
    $this->mid = Business::factory()->for($this->city)->at(33.6001, -7.5898)->create(['name' => 'Mid']);
    $this->far = Business::factory()->for($this->city)->at(33.7530, -7.5898)->create(['name' => 'Far']);
});

it('computes the distance in SQL like in PHP', function () {
    $business = Business::query()->withDistanceFrom($this->center)->whereKey($this->mid->id)->sole();

    expect((float) $business->distance_m)->toEqualWithDelta($this->center->distanceTo($this->mid->coordinates()), 5.0);
});

it('filters businesses within a radius', function () {
    $ids = Business::query()->withinDistance($this->center, 5_000)->pluck('id');

    expect($ids)->toContain($this->near->id, $this->mid->id)->not->toContain($this->far->id);
});

it('orders businesses by distance', function () {
    $names = Business::query()->orderByDistance($this->center)->pluck('name')->all();

    expect($names)->toBe(['Near', 'Mid', 'Far']);
});

it('keeps the raw spatial column out of the model and its serialization', function () {
    $business = Business::query()->find($this->near->id);

    expect($business->toArray())->not->toHaveKey('location')
        ->and($business->coordinates())->toBeInstanceOf(Coordinates::class);
});

it('updates the spatial point when coordinates change', function () {
    $this->far->update(['latitude' => 33.5740, 'longitude' => -7.5898]);

    $ids = Business::query()->withinDistance($this->center, 1_000)->pluck('id');

    expect($ids)->toContain($this->far->id);
});
