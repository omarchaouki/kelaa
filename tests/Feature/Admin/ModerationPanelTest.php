<?php

use App\Enums\Role;
use App\Filament\Widgets\ModerationOverview;
use App\Models\Business;
use App\Models\City;
use App\Models\Report;
use App\Models\Review;

beforeEach(function () {
    $business = Business::factory()->for(City::factory())->create();
    Review::factory()->for($business)->pending()->create(['body' => 'Avis en attente de modération']);
    Report::factory()->create();
});

it('shows the moderation queues to moderators', function (string $url) {
    $this->actingAs(staff(Role::Moderator))->get($url)->assertOk();
})->with(['/admin', '/admin/reviews', '/admin/review-replies', '/admin/reports', '/admin/businesses', '/admin/business-claims', '/admin/edit-suggestions']);

it('lists pending reviews first in the queue', function () {
    $this->actingAs(staff(Role::Moderator))->get('/admin/reviews')->assertSee('Avis en attente de modération');
});

it('keeps business owners out of every moderation queue', function (string $url) {
    $this->actingAs(staff(Role::BusinessOwner))->get($url)->assertForbidden();
})->with(['/admin/reviews', '/admin/reports', '/admin/businesses', '/admin/business-claims', '/admin/edit-suggestions']);

it('keeps the banned words list for admins', function () {
    $this->actingAs(staff(Role::Admin))->get('/admin/banned-words')->assertOk();
    $this->actingAs(staff(Role::Moderator))->get('/admin/banned-words')->assertForbidden();
});

it('shows revenue on the dashboard to admins only', function () {
    Livewire\Livewire::actingAs(staff(Role::Admin))->test(ModerationOverview::class)->assertSee('Revenus du mois');
    Livewire\Livewire::actingAs(staff(Role::Moderator))->test(ModerationOverview::class)
        ->assertDontSee('Revenus du mois')
        ->assertSee('Avis en attente');
});
