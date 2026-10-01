<?php

use App\Actions\Moderation\ModerateReview;
use App\Enums\PaymentStatus;
use App\Enums\ReplyStatus;
use App\Enums\ReviewStatus;
use App\Enums\Role;
use App\Exceptions\UnauthorizedStatusChange;
use App\Models\Business;
use App\Models\City;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Notifications\ContentRejected;
use App\Support\Billing\BillingGate;
use App\Support\Moderation\ModerationGate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->owner = staff(Role::BusinessOwner);
    $this->business = Business::factory()->for(City::factory())->create(['owner_id' => $this->owner->id]);
    $this->review = Review::factory()->for($this->business)->create(['status' => ReviewStatus::Published]);
    $this->moderate = app(ModerateReview::class);
});

it('refuses any status change outside moderation', function () {
    $this->review->forceFill(['status' => ReviewStatus::HiddenByModeration])->save();
})->throws(UnauthorizedStatusChange::class);

it('refuses a status change through a mass update of the model', function () {
    $this->review->forceFill(['moderation_reason' => 'x'])->save();
})->throws(UnauthorizedStatusChange::class);

it('refuses status changes by business owners and members, whatever they pay', function (Role $role) {
    $plan = Plan::factory()->create();
    $this->business->forceFill(['plan_id' => $plan->id, 'plan_expires_at' => now()->addYear()])->save();
    $actor = $role === Role::BusinessOwner ? $this->owner : staff($role);

    ModerationGate::asModerator($actor, fn () => $this->review->forceFill(['status' => ReviewStatus::HiddenByModeration])->save());
})->with([Role::Member, Role::BusinessOwner])->throws(AuthorizationException::class);

it('does not let business owners hide or delete reviews through policies', function () {
    expect($this->owner->can('moderate', $this->review))->toBeFalse()
        ->and($this->owner->can('delete', $this->review))->toBeFalse()
        ->and($this->owner->can('update', $this->review))->toBeFalse()
        ->and($this->owner->can('reply', $this->review))->toBeTrue();
});

it('lets moderators and admins change a review status', function (Role $role) {
    $this->moderate->hide(staff($role), $this->review, 'Contenu injurieux');

    expect($this->review->fresh()->status)->toBe(ReviewStatus::HiddenByModeration);
})->with([Role::Moderator, Role::Admin]);

it('requires a reason to reject or hide, and tells the author', function () {
    Notification::fake();
    $moderator = staff(Role::Moderator);
    $pending = Review::factory()->for($this->business)->pending()->create();

    expect(fn () => $this->moderate->reject($moderator, $pending, '  '))->toThrow(InvalidArgumentException::class);

    $this->moderate->reject($moderator, $pending, 'Avis sans rapport avec l’établissement');

    $pending->refresh();
    expect($pending->status)->toBe(ReviewStatus::Rejected)
        ->and($pending->moderated_by)->toBe($moderator->id)
        ->and($pending->moderation_reason)->toBe('Avis sans rapport avec l’établissement');
    Notification::assertSentTo($pending->user, ContentRejected::class, fn ($n) => $n->reason === 'Avis sans rapport avec l’établissement');
});

it('logs every moderation decision with who, when and why', function () {
    $moderator = staff(Role::Moderator);

    $this->moderate->hide($moderator, $this->review, 'Spam');

    $activity = Activity::query()->where('event', null)->where('description', 'review.hidden')->sole();
    expect($activity->causer_id)->toBe($moderator->id)
        ->and($activity->subject_id)->toBe($this->review->id)
        ->and($activity->properties['reason'])->toBe('Spam')
        ->and($activity->created_at)->not->toBeNull();
});

it('never lets a payment change what reviews are visible', function () {
    $hidden = Review::factory()->for($this->business)->create(['body' => 'Texte masqué par la modération 123']);
    $this->moderate->hide(staff(Role::Moderator), $hidden, 'Hors sujet');
    $pending = Review::factory()->for($this->business)->pending()->create();

    // The business pays and its payment is validated.
    $payment = Payment::factory()->for($this->business)->create();
    BillingGate::asSystem(fn () => $payment->forceFill(['status' => PaymentStatus::Validated, 'validated_at' => now()])->save());
    $this->business->forceFill(['plan_id' => Plan::factory()->create()->id, 'plan_expires_at' => now()->addYear()])->save();

    expect($hidden->fresh()->status)->toBe(ReviewStatus::HiddenByModeration)
        ->and($pending->fresh()->status)->toBe(ReviewStatus::Pending)
        ->and($this->business->publishedReviews()->pluck('id')->all())->toBe([$this->review->id]);

    $this->get(route('city.business', ['locale' => 'fr', 'city' => $this->business->city, 'business' => $this->business]))
        ->assertOk()
        ->assertDontSee((string) $hidden->body);
});

it('protects replies with the same rule', function () {
    $reply = ReviewReply::factory()->for($this->review)->create(['user_id' => $this->owner->id]);

    $reply->forceFill(['status' => ReplyStatus::HiddenByModeration])->save();
})->throws(UnauthorizedStatusChange::class);
