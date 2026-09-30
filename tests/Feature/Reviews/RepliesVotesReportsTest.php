<?php

use App\Actions\Moderation\HandleReport;
use App\Actions\Reports\SubmitReport;
use App\Actions\Reviews\ReplyToReview;
use App\Actions\Reviews\ToggleHelpfulVote;
use App\Enums\BusinessStatus;
use App\Enums\ReplyStatus;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Enums\Role;
use App\Exceptions\TooManyReports;
use App\Livewire\Directory\BusinessPage;
use App\Models\BannedWord;
use App\Models\Business;
use App\Models\City;
use App\Models\Report;
use App\Models\Review;
use App\Notifications\ReplyToYourReview;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->city = City::factory()->create();
    $this->owner = staff(Role::BusinessOwner);
    $this->business = Business::factory()->for($this->city)->create(['owner_id' => $this->owner->id]);
    $this->review = Review::factory()->for($this->business)->create(['body' => 'Service correct mais un peu lent.']);
});

it('lets the owner reply once, edits replace the reply, and notifies the author', function () {
    Notification::fake();
    $reply = app(ReplyToReview::class);

    $first = $reply($this->owner, $this->review, 'Merci pour votre retour, nous allons nous améliorer.');
    $second = $reply($this->owner, $this->review, 'Merci, nous avons embauché un second technicien.');

    expect($second->id)->toBe($first->id)
        ->and($second->body)->toBe('Merci, nous avons embauché un second technicien.')
        ->and($second->edited_at)->not->toBeNull()
        ->and($second->status)->toBe(ReplyStatus::Published);
    Notification::assertSentToTimes($this->review->user, ReplyToYourReview::class, 1);
});

it('refuses replies from anyone but the owner', function () {
    app(ReplyToReview::class)(member(), $this->review, 'Je ne suis pas le propriétaire.');
})->throws(AuthorizationException::class);

it('sends replies with banned words to moderation', function () {
    BannedWord::query()->create(['word' => 'menteur']);

    $reply = app(ReplyToReview::class)($this->owner, $this->review, 'Ce client est un menteur.');

    expect($reply->status)->toBe(ReplyStatus::Pending);
});

it('toggles a helpful vote once per user and keeps the counter exact', function () {
    $voter = member();
    $toggle = app(ToggleHelpfulVote::class);

    expect($toggle($voter, $this->review))->toBeTrue()
        ->and($this->review->fresh()->helpful_count)->toBe(1)
        ->and($toggle($voter, $this->review))->toBeFalse()
        ->and($this->review->fresh()->helpful_count)->toBe(0);
});

it('does not let authors vote for their own review', function () {
    app(ToggleHelpfulVote::class)($this->review->user, $this->review);
})->throws(AuthorizationException::class);

it('deduplicates reports per reporter and target', function () {
    $reporter = member();
    $submit = app(SubmitReport::class);

    $submit($reporter, $this->review, ReportReason::Fake, 'Faux avis');
    $submit($reporter, $this->review, ReportReason::Spam, null);
    $submit(member(), $this->review, ReportReason::Spam, null);

    expect(Report::query()->count())->toBe(2);
});

it('rate limits reports', function () {
    config(['reviews.reports_per_hour' => 2]);
    $reporter = member();
    $submit = app(SubmitReport::class);
    $others = Review::factory()->count(3)->for($this->business)->create();

    foreach ($others as $review) {
        $submit($reporter, $review, ReportReason::Spam, null);
    }
})->throws(TooManyReports::class);

it('refuses to report hidden content or private files', function () {
    $pending = Review::factory()->for($this->business)->pending()->create();

    expect(fn () => app(SubmitReport::class)(member(), $pending, ReportReason::Spam, null))->toThrow(InvalidArgumentException::class);
});

it('upholding a report hides the review and closes every open report on it', function () {
    $submit = app(SubmitReport::class);
    $report = $submit(member(), $this->review, ReportReason::Offensive, null);
    $submit(member(), $this->review, ReportReason::Spam, null);

    app(HandleReport::class)->uphold(staff(Role::Moderator), $report, 'Propos injurieux');

    expect($this->review->fresh()->status)->toBe(ReviewStatus::HiddenByModeration)
        ->and(Report::query()->where('status', ReportStatus::Open)->count())->toBe(0);
});

it('upholding a report on a business suspends it', function () {
    $report = app(SubmitReport::class)(member(), $this->business, ReportReason::ClosedBusiness, null);

    app(HandleReport::class)->uphold(staff(Role::Admin), $report, 'Établissement fermé définitivement');

    expect($this->business->fresh()->status)->toBe(BusinessStatus::Suspended);
});

it('only moderators can handle reports', function () {
    $report = app(SubmitReport::class)(member(), $this->review, ReportReason::Spam, null);

    app(HandleReport::class)->dismiss($this->owner, $report, 'Je ne suis pas d’accord');
})->throws(AuthorizationException::class);

describe('business page', function () {
    beforeEach(function () {
        $this->reporter = member();
    });

    it('reports content of this business through the page', function () {
        Livewire::actingAs($this->reporter)
            ->test(BusinessPage::class, ['city' => $this->city, 'business' => $this->business])
            ->call('openReport', 'review', $this->review->id)
            ->assertSet('reportType', 'review')
            ->set('reportReason', 'fake')
            ->call('submitReport')
            ->assertHasNoErrors()
            ->assertSet('reportType', null);

        expect(Report::query()->sole()->reportable_id)->toBe($this->review->id);
    });

    it('refuses to target content of another business through the page', function () {
        $foreign = Review::factory()->for(Business::factory()->for($this->city))->create();

        Livewire::actingAs($this->reporter)
            ->test(BusinessPage::class, ['city' => $this->city, 'business' => $this->business])
            ->call('openReport', 'review', $foreign->id)
            ->assertSet('reportType', null)
            ->call('toggleHelpful', $foreign->id);

        expect(Report::query()->count())->toBe(0)->and($foreign->fresh()->helpful_count)->toBe(0);
    });

    it('sends guests to the login page before reporting or voting', function () {
        Livewire::test(BusinessPage::class, ['city' => $this->city, 'business' => $this->business])
            ->call('openReport', 'business', $this->business->id)
            ->assertRedirect(route('login'));
    });

    it('lets only the owner reply from the page', function () {
        Livewire::actingAs($this->reporter)
            ->test(BusinessPage::class, ['city' => $this->city, 'business' => $this->business])
            ->set("replyBodies.{$this->review->id}", 'Je me fais passer pour le propriétaire')
            ->call('saveReply', $this->review->id);

        expect($this->review->reply()->exists())->toBeFalse();

        Livewire::actingAs($this->owner)
            ->test(BusinessPage::class, ['city' => $this->city, 'business' => $this->business])
            ->set("replyBodies.{$this->review->id}", 'Merci beaucoup pour votre avis !')
            ->call('saveReply', $this->review->id)
            ->assertHasNoErrors();

        expect($this->review->reply()->sole()->body)->toBe('Merci beaucoup pour votre avis !');
    });
});
