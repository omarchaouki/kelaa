<?php

use App\Enums\SearchSort;
use App\Services\Search\SearchFilters;

it('keeps valid filter values', function () {
    $filters = SearchFilters::fromInput([
        'q' => '  plombier   maarif ',
        'open' => '1',
        'rating' => '4.5',
        'price' => ['1', '3'],
        'verified' => 'true',
        'radius' => '5',
        'sort' => 'distance',
    ]);

    expect($filters->query)->toBe('plombier maarif')
        ->and($filters->openNow)->toBeTrue()
        ->and($filters->minRating)->toBe(4.5)
        ->and($filters->priceRanges)->toBe([1, 3])
        ->and($filters->verifiedOnly)->toBeTrue()
        ->and($filters->radiusKm)->toBe(5)
        ->and($filters->sort)->toBe(SearchSort::Distance);
});

it('drops anything outside the whitelists', function () {
    $filters = SearchFilters::fromInput([
        'q' => ['array', 'injection'],
        'open' => 'maybe',
        'rating' => '4.7',
        'price' => ['0', '5', 'DROP TABLE', '2'],
        'radius' => '9999',
        'sort' => 'price desc; --',
    ]);

    expect($filters->query)->toBe('')
        ->and($filters->openNow)->toBeFalse()
        ->and($filters->minRating)->toBeNull()
        ->and($filters->priceRanges)->toBe([2])
        ->and($filters->radiusKm)->toBeNull()
        ->and($filters->sort)->toBe(SearchSort::Relevance);
});

it('caps the query length and strips control characters', function () {
    $filters = SearchFilters::fromInput(['q' => "mec\u{0000}anicien\u{202E}".str_repeat('a', 500)]);

    expect(mb_strlen($filters->query))->toBe(SearchFilters::MAX_QUERY_LENGTH)
        ->and($filters->query)->not->toContain("\u{0000}", "\u{202E}");
});
