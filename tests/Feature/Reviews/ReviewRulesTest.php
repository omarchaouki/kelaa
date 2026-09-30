<?php

use App\Actions\Moderation\ModerateReview;
use App\Actions\Reviews\SubmitReview;
use App\Enums\ReviewStatus;
use App\Enums\Role;
use App\Exceptions\ReviewNotAllowed;
use App\Jobs\RecalculateBusinessRating;
use App\Models\BannedWord;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewReviewOnYourBusiness;
use App\Services\Moderation\BannedWordFilter;
use App\Services\Reviews\ReviewAbuseDetector;
use App\Services\Reviews\ReviewDenial;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->city = City::factory()->create();
    $this->plumbers = Category::factory()->create();
    $this->owner = User::factory()->create();
    $this->business = Business::factory()->for($this->city)->inCategory($this->plumbers)->create(['owner_id' => $this->owner->id, 'phone' => '0522000000', 'whatsapp' => '0611111111']);
    $this->author = User::factory()->create(['created_at' => now()->subMonth()]);
    $this->submit = app(SubmitReview::class);
});

function review(User $author, Business $business, int $rating = 4, ?string $body = null, string $ip = 'ip-a', string $device = 'device-a'): Review
{
    return app(SubmitReview::class)($author, $business, $rating, $body, $ip, $device);
}

function denial(callable $callback): ?ReviewDenial
{
    try {
        $callback();
    } catch (ReviewNotAllowed $e) {
        return $e->denial;
    }

    return null;
}

it('publishes a normal review immediately and notifies the owner', function () {
    Notification::fake();

    $review = review($this->author, $this->business, 5, 'Travail rapide et très propre, je recommande.');

    expect($review->status)->toBe(ReviewStatus::Published)
        ->and($review->published_at)->not->toBeNull()
        ->and($review->flags)->toBeNull();
    Notification::assertSentTo($this->owner, NewReviewOnYourBusiness::class);
});

it('requires a verified phone', function () {
    $user = User::factory()->unverifiedPhone()->create();

    expect(denial(fn () => review($user, $this->business)))->toBe(ReviewDenial::PhoneNotVerified);
});

it('refuses banned users', function () {
    expect(denial(fn () => review(User::factory()->banned()->create(), $this->business)))->toBe(ReviewDenial::Banned);
});

it('refuses reviews of unpublished businesses', function () {
    $pending = Business::factory()->for($this->city)->pending()->create();

    expect(denial(fn () => review($this->author, $pending)))->toBe(ReviewDenial::BusinessUnavailable);
});

it('forbids owners to review their own business', function () {
    expect(denial(fn () => review($this->owner, $this->business)))->toBe(ReviewDenial::OwnBusiness);
});

it('forbids owners to review a competitor with the same primary category in the same city', function () {
    $competitorOwner = User::factory()->create();
    Business::factory()->for($this->city)->inCategory($this->plumbers)->create(['owner_id' => $competitorOwner->id]);

    expect(denial(fn () => review($competitorOwner, $this->business)))->toBe(ReviewDenial::ConflictOfInterest);
});

it('lets owners review businesses of other categories or other cities', function () {
    $restaurantOwner = User::factory()->create();
    Business::factory()->for($this->city)->inCategory(Category::factory()->create())->create(['owner_id' => $restaurantOwner->id]);

    $otherCityOwner = User::factory()->create();
    Business::factory()->for(City::factory()->create())->inCategory($this->plumbers)->create(['owner_id' => $otherCityOwner->id]);

    expect(review($restaurantOwner, $this->business)->exists)->toBeTrue()
        ->and(review($otherCityOwner, $this->business)->exists)->toBeTrue();
});

it('allows one review per user and business, later submissions edit it and keep the history', function () {
    $first = review($this->author, $this->business, 2, 'Pas terrible, le plombier est arrivé en retard.');
    $second = review($this->author, $this->business, 4, 'Finalement le problème est réglé, merci pour le suivi.');

    expect($second->id)->toBe($first->id)
        ->and(Review::query()->count())->toBe(1)
        ->and($second->rating)->toBe(4)
        ->and($second->edited_at)->not->toBeNull()
        ->and($second->revisions()->sole()->rating)->toBe(2);
});

it('enforces the unique constraint in the database', function () {
    Review::factory()->for($this->business)->for($this->author)->create();
    Review::factory()->for($this->business)->for($this->author)->create();
})->throws(UniqueConstraintViolationException::class);

it('limits new reviews per day but not edits', function () {
    config(['reviews.daily_limit' => 2]);
    $businesses = Business::factory()->count(3)->for($this->city)->create();

    review($this->author, $businesses[0]);
    review($this->author, $businesses[1]);

    expect(denial(fn () => review($this->author, $businesses[2])))->toBe(ReviewDenial::DailyLimit)
        ->and(review($this->author, $businesses[0], 1)->rating)->toBe(1);

    $this->travel(25)->hours();
    expect(review($this->author, $businesses[2])->exists)->toBeTrue();
});

it('sends suspicious reviews to moderation with the reasons', function (Closure $setup, string $flag) {
    [$author, $body, $ip, $device] = $setup($this);

    $review = review($author, $this->business, 5, $body, $ip, $device);

    expect($review->status)->toBe(ReviewStatus::Pending)
        ->and($review->published_at)->toBeNull()
        ->and($review->flags)->toContain($flag);
})->with([
    'account younger than 24h' => [fn ($t) => [User::factory()->newAccount()->create(), null, 'ip-x', 'dev-x'], ReviewAbuseDetector::NEW_ACCOUNT],
    'same IP as another review' => [function ($t) {
        review($t->author, $t->business, 5, null, 'shared-ip', 'dev-1');

        return [User::factory()->create(['created_at' => now()->subYear()]), null, 'shared-ip', 'dev-2'];
    }, ReviewAbuseDetector::SAME_IP],
    'same device as another review' => [function ($t) {
        review($t->author, $t->business, 5, null, 'ip-1', 'shared-device');

        return [User::factory()->create(['created_at' => now()->subYear()]), null, 'ip-2', 'shared-device'];
    }, ReviewAbuseDetector::SAME_DEVICE],
    'burst on one business' => [function ($t) {
        config(['reviews.abuse.burst_count' => 3]);
        Review::factory()->count(3)->for($t->business)->create(['created_at' => now()->subHour()]);

        return [User::factory()->create(['created_at' => now()->subYear()]), null, 'ip-9', 'dev-9'];
    }, ReviewAbuseDetector::BURST],
    'reviewer phone is the business phone' => [fn ($t) => [User::factory()->create(['phone' => '+212611111111', 'created_at' => now()->subYear()]), null, 'ip-7', 'dev-7'], ReviewAbuseDetector::OWNER_PHONE],
    'banned words' => [function ($t) {
        BannedWord::query()->create(['word' => 'arnaque']);

        return [User::factory()->create(['created_at' => now()->subYear()]), 'Une vraie ARNAAAQUE ce garage, fuyez !', 'ip-8', 'dev-8'];
    }, ReviewAbuseDetector::BANNED_WORDS],
]);

it('matches banned words regardless of accents and Arabic diacritics', function () {
    BannedWord::query()->create(['word' => 'نصاب']);
    BannedWord::query()->create(['word' => 'très mauvais']);

    $filter = app(BannedWordFilter::class);

    expect($filter->containsBannedWords('هذا نَصّاب'))->toBeTrue()      // diacritics (shadda) ignored
        ->and($filter->containsBannedWords('هذا نَصَاب حقيقي'))->toBeTrue()
        ->and($filter->containsBannedWords('نصابين'))->toBeFalse()        // whole words only
        ->and($filter->containsBannedWords('Service TRES MAUVAIS'))->toBeTrue()
        ->and($filter->containsBannedWords('très bon service'))->toBeFalse();
});

it('re-checks an edit: flagged edits and edits of rejected reviews go back to moderation', function () {
    $review = review($this->author, $this->business, 5, 'Excellent travail, rapide et soigné.');
    BannedWord::query()->create(['word' => 'escroc']);

    $edited = review($this->author, $this->business, 1, 'Au final un escroc, ne payez rien avant.');

    expect($edited->status)->toBe(ReviewStatus::Pending)->and($edited->flags)->toContain(ReviewAbuseDetector::BANNED_WORDS);
});

it('never lets the author un-hide a review by editing it', function () {
    $review = review($this->author, $this->business, 5, 'Excellent travail, rapide et soigné.');
    app(ModerateReview::class)->hide(staff(Role::Moderator), $review, 'Propos hors sujet');

    $edited = review($this->author, $this->business, 4, 'Je modifie mon avis pour le rendre visible.');

    expect($edited->status)->toBe(ReviewStatus::HiddenByModeration);
});

it('rejects texts shorter than the minimum through the form rules', function () {
    expect(SubmitReview::cleanBody("  \u{202E}ok\u{0000}  "))->toBe('ok');
});

it('recalculates the rating after every change', function () {
    Queue::fake();

    review($this->author, $this->business);

    Queue::assertPushed(RecalculateBusinessRating::class, fn ($job) => $job->businessId === $this->business->id);
});
