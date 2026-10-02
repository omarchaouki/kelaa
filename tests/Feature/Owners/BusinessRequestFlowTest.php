<?php

use App\Actions\Moderation\ModerateBusiness;
use App\Enums\BusinessStatus;
use App\Enums\Role;
use App\Filament\Resources\BusinessRequests\Pages\ListBusinessRequests;
use App\Livewire\Owners\AddBusiness;
use App\Livewire\Owners\Dashboard;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Notifications\BusinessRequestDecided;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $this->city = City::factory()->create(['slug' => 'ma-ville', 'center_latitude' => 33.5731, 'center_longitude' => -7.5898]);
    $this->taxis = Category::factory()->childOf(Category::factory()->create())->create(['slug' => 'petits-taxis']);
    $this->user = member();
});

it('walks through the steps, validating each one before moving on', function () {
    $form = Livewire::actingAs($this->user)->test(AddBusiness::class, ['city' => $this->city])
        ->assertSet('step', 1)
        ->call('next')->assertHasErrors(['name', 'categoryId'])->assertSet('step', 1)
        ->set('name', 'Taxi Ahmed')->set('categoryId', $this->taxis->id)
        ->call('next')->assertHasNoErrors()->assertSet('step', 2)
        // A listing needs at least one number.
        ->call('next')->assertHasErrors('phone')->assertSet('step', 2)
        ->set('whatsapp', '06 12 34 56 78')
        ->call('next')->assertHasNoErrors()->assertSet('step', 3)
        ->set('address', 'Avenue Mohammed V')
        ->call('next')->assertHasErrors('latitude')
        ->set('latitude', 33.574)->set('longitude', -7.59)
        ->call('next')->assertSet('step', 4)
        ->set('alwaysOpen', true)
        ->call('next')->assertSet('step', 5)->assertSet('checked', true)
        ->assertSee('Taxi Ahmed');

    $form->call('submit')->assertHasNoErrors()->assertSet('submittedName', 'Taxi Ahmed');

    expect(Business::query()->where('name', 'Taxi Ahmed')->sole())
        ->status->toBe(BusinessStatus::Pending)
        ->submitted_by->toBe($this->user->id);
});

it('only lets the visitor jump back to steps already reached', function () {
    Livewire::actingAs($this->user)->test(AddBusiness::class, ['city' => $this->city])
        ->call('goToStep', 4)->assertSet('step', 1)
        ->set('name', 'Taxi Ahmed')->set('categoryId', $this->taxis->id)->call('next')
        ->call('goToStep', 1)->assertSet('step', 1)
        ->call('goToStep', 2)->assertSet('step', 2);
});

it('cannot skip steps by changing the step from the browser', function () {
    Livewire::actingAs($this->user)->test(AddBusiness::class, ['city' => $this->city])
        ->set('step', 5);
})->throws(CannotUpdateLockedPropertyException::class);

it('keeps at most two extra categories', function () {
    $others = Category::factory()->childOf($this->taxis->parent()->first())->count(3)->create();

    $form = Livewire::actingAs($this->user)->test(AddBusiness::class, ['city' => $this->city]);
    foreach ($others as $other) {
        $form->call('toggleExtraCategory', $other->id);
    }

    expect($form->get('extraCategoryIds'))->toHaveCount(2);
});

it('publishes a request and tells the person who proposed it', function () {
    Notification::fake();
    $business = Business::factory()->for($this->city)->inCategory($this->taxis)->pending()->create(['submitted_by' => $this->user->id]);

    app(ModerateBusiness::class)->publish(staff(Role::Moderator), $business);

    expect($business->fresh()->status)->toBe(BusinessStatus::Published);
    Notification::assertSentTo($this->user, BusinessRequestDecided::class, fn ($n) => $n->published && str_contains((string) $n->url, $business->slug));
});

it('declines a request with a reason the submitter can read', function () {
    Notification::fake();
    $business = Business::factory()->for($this->city)->inCategory($this->taxis)->pending()->create(['submitted_by' => $this->user->id]);

    app(ModerateBusiness::class)->decline(staff(Role::Moderator), $business, 'Numéro injoignable');

    expect($business->fresh())
        ->status->toBe(BusinessStatus::Rejected)
        ->rejection_reason->toBe('Numéro injoignable');
    Notification::assertSentTo($this->user, BusinessRequestDecided::class, fn ($n) => ! $n->published && $n->reason === 'Numéro injoignable');

    // Never public, and visible in the submitter's dashboard with the reason.
    $this->get('/fr/ma-ville/b/'.$business->slug)->assertNotFound();
    Livewire::actingAs($this->user)->test(Dashboard::class)->assertSee('Refusée')->assertSee('Numéro injoignable');
});

it('only declines pending requests and always needs a reason', function () {
    $published = Business::factory()->for($this->city)->inCategory($this->taxis)->create();
    $pending = Business::factory()->for($this->city)->inCategory($this->taxis)->pending()->create();

    expect(fn () => app(ModerateBusiness::class)->decline(staff(Role::Moderator), $published, 'x'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => app(ModerateBusiness::class)->decline(staff(Role::Moderator), $pending, '  '))->toThrow(InvalidArgumentException::class);
});

it('lists pending requests for moderators, not for members', function () {
    $pending = Business::factory()->for($this->city)->inCategory($this->taxis)->pending()->create(['name' => 'Demande en attente']);
    Business::factory()->for($this->city)->inCategory($this->taxis)->create(['name' => 'Déjà publiée']);

    $this->actingAs(member())->get('/admin/business-requests')->assertForbidden();

    $this->actingAs(staff(Role::Moderator));
    $this->get('/admin/business-requests')->assertOk();

    Livewire::test(ListBusinessRequests::class)
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords(Business::query()->where('name', 'Déjà publiée')->get())
        ->callTableAction('decline', $pending, data: ['reason' => 'Doublon'])
        ->assertHasNoTableActionErrors();

    expect($pending->fresh()->status)->toBe(BusinessStatus::Rejected);
});

it('approves from the admin queue', function () {
    $pending = Business::factory()->for($this->city)->inCategory($this->taxis)->pending()->create();

    $this->actingAs(staff(Role::Moderator));
    Livewire::test(ListBusinessRequests::class)->callTableAction('approve', $pending)->assertHasNoTableActionErrors();

    expect($pending->fresh()->status)->toBe(BusinessStatus::Published);
});
