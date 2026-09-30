<?php

use App\Enums\ReviewStatus;
use App\Enums\SearchSort;
use App\Jobs\RecalculateBusinessRating;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Plan;
use App\Models\Review;
use App\Models\SponsoredPlacement;
use App\Services\Ranking\RankingService;
use App\Services\Ranking\RankingSignals;
use App\Services\Search\BusinessSearch;
use App\Services\Search\SearchFilters;

beforeEach(function () {
    $this->ranking = app(RankingService::class);
});

it('computes the Bayesian average from the spec formula', function () {
    // score = (v / (v + m)) * R + (m / (v + m)) * C
    expect($this->ranking->bayesian(5.0, 1, 3.8, 5))->toBe(round((1 / 6) * 5 + (5 / 6) * 3.8, 4))
        ->and($this->ranking->bayesian(4.7, 80, 3.8, 5))->toBe(round((80 / 85) * 4.7 + (5 / 85) * 3.8, 4))
        ->and($this->ranking->bayesian(0.0, 0, 3.8, 5))->toBe(3.8);
});

it('does not let one 5-star review beat 80 reviews at 4.7', function () {
    expect($this->ranking->bayesian(5.0, 1, 3.8))->toBeLessThan($this->ranking->bayesian(4.7, 80, 3.8));
});

it('uses the configurable m', function () {
    config(['ranking.bayesian_m' => 50]);

    expect($this->ranking->bayesian(5.0, 10, 3.0))->toBe(round((10 / 60) * 5 + (50 / 60) * 3.0, 4));
});

it('computes C from published reviews of the same primary category in the same city', function () {
    $city = City::factory()->create();
    $category = Category::factory()->create();
    $a = Business::factory()->for($city)->inCategory($category)->create();
    $b = Business::factory()->for($city)->inCategory($category)->create();
    $otherCity = Business::factory()->for(City::factory())->inCategory($category)->create();

    Review::factory()->for($a)->rating(5)->create();
    Review::factory()->for($b)->rating(3)->create();
    Review::factory()->for($b)->rating(1)->pending()->create();   // not published
    Review::factory()->for($otherCity)->rating(1)->create();      // other city

    expect($this->ranking->categoryAverage($city->id, $category->id))->toBe(4.0)
        ->and($this->ranking->categoryAverage($city->id, Category::factory()->create()->id))->toBe((float) config('ranking.fallback_average'));
});

it('recalculates stored ratings of the business and its category peers', function () {
    $city = City::factory()->create();
    $category = Category::factory()->create();
    $business = Business::factory()->for($city)->inCategory($category)->create();
    $peer = Business::factory()->for($city)->inCategory($category)->create();
    Review::factory()->for($business)->rating(5)->count(2)->create();
    Review::factory()->for($business)->rating(2)->pending()->create();

    (new RecalculateBusinessRating($business->id))->handle($this->ranking);

    $business->refresh();
    $peer->refresh();
    expect($business->rating_avg)->toBe(5.0)
        ->and($business->rating_count)->toBe(2)
        ->and($business->bayesian_score)->toBe($this->ranking->bayesian(5.0, 2, 5.0))
        ->and($peer->bayesian_score)->toBe(5.0); // no reviews: C, which is now 5.0
});

it('combines the relevance signals with the configured weights', function () {
    $strong = new RankingSignals(bayesianScore: 4.8, profileCompleteness: 100, isVerified: true, lastActivityAt: now());
    $weak = new RankingSignals(bayesianScore: 3.0, profileCompleteness: 20, isVerified: false, lastActivityAt: now()->subYears(2));

    expect($this->ranking->relevance($strong))->toBeGreaterThan($this->ranking->relevance($weak))
        ->and($this->ranking->relevance($strong))->toBeLessThanOrEqual(1.0)
        ->and($this->ranking->relevance($weak))->toBeGreaterThanOrEqual(0.0);
});

it('favours nearer and better text matches', function () {
    $base = fn (?float $distance, ?float $rank) => new RankingSignals(4.0, 50, false, now(), $distance, $rank, $rank === null ? null : 10);

    expect($this->ranking->relevance($base(200, null)))->toBeGreaterThan($this->ranking->relevance($base(8000, null)))
        ->and($this->ranking->relevance($base(null, 0)))->toBeGreaterThan($this->ranking->relevance($base(null, 9)));
});

it('ignores paid plans and sponsored placements in organic ranking', function () {
    $city = City::factory()->create();
    $category = Category::factory()->create();
    $attributes = ['bayesian_score' => 4.0, 'profile_completeness' => 60, 'is_verified' => false, 'last_activity_at' => now()->subDays(3), 'latitude' => 33.57, 'longitude' => -7.59];

    $free = Business::factory()->for($city)->inCategory($category)->create(['name' => 'Free'] + $attributes);
    $premium = Business::factory()->for($city)->inCategory($category)->create(['name' => 'Premium'] + $attributes);
    $premium->forceFill(['plan_id' => Plan::factory()->create(['features' => ['highlighted_card' => true, 'remove_competitor_ads' => true, 'priority_support' => true, 'max_photos' => 40, 'show_stats' => true]])->id, 'plan_expires_at' => now()->addYear()])->save();
    SponsoredPlacement::factory()->create(['business_id' => $premium->id, 'city_id' => $city->id, 'category_id' => $category->id]);

    $signals = fn (Business $b) => new RankingSignals($b->bayesian_score, $b->profile_completeness, $b->is_verified, $b->last_activity_at);

    expect($this->ranking->relevance($signals($premium->fresh())))->toBe($this->ranking->relevance($signals($free->fresh())));

    // Equal scores: the tie-break is the id, so the free business created first stays first.
    $organic = app(BusinessSearch::class)->search($city, new SearchFilters(sort: SearchSort::Relevance));
    expect(collect($organic->items())->pluck('name')->all())->toBe(['Free', 'Premium']);

    // A paid plan never outranks better reviews.
    $free->forceFill(['bayesian_score' => 4.5])->save();
    $organic = app(BusinessSearch::class)->search($city, new SearchFilters(sort: SearchSort::Relevance));
    expect(collect($organic->items())->pluck('name')->first())->toBe('Free');
});

it('keeps pending, rejected and hidden reviews out of the rating', function () {
    $business = Business::factory()->create();
    Review::factory()->for($business)->rating(5)->create();
    foreach ([ReviewStatus::Pending, ReviewStatus::Rejected, ReviewStatus::HiddenByModeration] as $status) {
        Review::factory()->for($business)->rating(1)->create(['status' => $status]);
    }

    $this->ranking->recalculate($business);

    expect($business->fresh()->rating_count)->toBe(1)->and($business->fresh()->rating_avg)->toBe(5.0);
});

it('recalculates everything from the nightly command', function () {
    $business = Business::factory()->create(['rating_avg' => 0, 'rating_count' => 0]);
    Review::factory()->for($business)->rating(4)->create();

    $this->artisan('ranking:recalculate')->assertSuccessful();

    expect($business->fresh()->rating_count)->toBe(1);
});
