<?php

use App\Support\Geo\Coordinates;

it('computes great-circle distances in meters', function () {
    $casablanca = new Coordinates(33.5731, -7.5898);
    $rabat = new Coordinates(34.0209, -6.8416);

    // ~85 km as the crow flies.
    expect($casablanca->distanceTo($rabat))->toBeBetween(84_000, 86_500)
        ->and($casablanca->distanceTo($casablanca))->toBe(0.0);
});

it('builds a bounding box that contains every point within the radius', function () {
    $center = new Coordinates(33.5731, -7.5898);
    $box = $center->boundingBox(1000);

    $north = new Coordinates(33.5731 + 0.0089, -7.5898); // ~990 m north
    $east = new Coordinates(33.5731, -7.5898 + 0.0107);   // ~990 m east

    expect($north->latitude)->toBeLessThanOrEqual($box['max_lat'])
        ->and($east->longitude)->toBeLessThanOrEqual($box['max_lng'])
        ->and($center->distanceTo($north))->toBeLessThan(1000)
        ->and($center->distanceTo($east))->toBeLessThan(1000);
});

it('writes WKT in longitude-latitude order', function () {
    expect((new Coordinates(33.5, -7.25))->toWkt())->toBe('POINT(-7.2500000 33.5000000)');
});

it('rejects out of range coordinates', function () {
    new Coordinates(91, 0);
})->throws(InvalidArgumentException::class);

it('returns null from tryFrom for invalid input', function () {
    expect(Coordinates::tryFrom('abc', 1))->toBeNull()
        ->and(Coordinates::tryFrom(100, 1))->toBeNull()
        ->and(Coordinates::tryFrom('33.5', '-7.5'))->toBeInstanceOf(Coordinates::class);
});
