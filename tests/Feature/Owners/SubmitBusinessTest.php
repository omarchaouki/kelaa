<?php

use App\Actions\Owners\SubmitBusiness;
use App\Enums\BusinessStatus;
use App\Exceptions\RateLimited;
use App\Livewire\Owners\AddBusiness;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\User;
use App\Services\Owners\DuplicateDetector;
use App\Support\Geo\Coordinates;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->city = City::factory()->create(['slug' => 'ma-ville', 'center_latitude' => 33.5731, 'center_longitude' => -7.5898]);
    $this->group = Category::factory()->create();
    $this->plumbers = Category::factory()->childOf($this->group)->create();
    $this->user = member();
});

function businessData(City $city, array $overrides = []): array
{
    return $overrides + [
        'city_id' => $city->id, 'neighborhood_id' => null, 'name' => 'Plomberie Atlas', 'description' => 'Dépannage rapide 7j/7.',
        'address' => '12 rue de Fès', 'latitude' => 33.5740, 'longitude' => -7.5900, 'phone' => null, 'whatsapp' => '0612345678',
        'website' => null, 'price_range' => 2, 'is_always_open' => false,
    ];
}

it('creates a pending listing with hours and a primary category', function () {
    $business = app(SubmitBusiness::class)($this->user, businessData($this->city), [$this->plumbers->id], [1 => [['09:00', '18:00']]]);

    expect($business->status)->toBe(BusinessStatus::Pending)
        ->and($business->submitted_by)->toBe($this->user->id)
        ->and($business->owner_id)->toBeNull()
        ->and($business->whatsapp)->toBe('+212612345678')
        ->and($business->hours()->count())->toBe(1)
        ->and($business->categories()->wherePivot('is_primary', true)->sole()->id)->toBe($this->plumbers->id);

    $this->get('/fr/ma-ville/b/'.$business->slug)->assertNotFound();
});

it('requires a verified phone to submit', function () {
    app(SubmitBusiness::class)(User::factory()->unverifiedPhone()->create(), businessData($this->city), [$this->plumbers->id], []);
})->throws(AuthorizationException::class);

it('limits submissions per day', function () {
    config(['owners.submissions_per_day' => 2]);
    $submit = app(SubmitBusiness::class);

    foreach (range(1, 3) as $i) {
        $submit($this->user, businessData($this->city, ['name' => "Commerce {$i}", 'whatsapp' => null, 'latitude' => 33.57 + $i / 100]), [$this->plumbers->id], []);
    }
})->throws(RateLimited::class);

it('detects duplicates by phone and by similar name within 200 m', function () {
    $existing = Business::factory()->for($this->city)->at(33.5740, -7.5900)->create(['name' => 'Plomberie Atlas', 'phone' => '0522111111', 'whatsapp' => '0699999999']);
    $detector = app(DuplicateDetector::class);

    expect($detector->find($this->city->id, 'Autre nom', null, '05 22 11 11 11', null)->pluck('id')->all())->toBe([$existing->id])
        ->and($detector->find($this->city->id, 'Chez Plomberie Atlas', new Coordinates(33.5745, -7.5900), null, null)->pluck('id')->all())->toBe([$existing->id])
        ->and($detector->find($this->city->id, 'Plomberie Atlas', new Coordinates(33.6000, -7.5900), null, null))->toBeEmpty()   // ~3 km away
        ->and($detector->find($this->city->id, 'Boulangerie Nour', new Coordinates(33.5741, -7.5900), null, null))->toBeEmpty();
});

it('records possible duplicates for moderators', function () {
    $existing = Business::factory()->for($this->city)->create(['whatsapp' => '0612345678']);

    $business = app(SubmitBusiness::class)($this->user, businessData($this->city), [$this->plumbers->id], []);

    expect($business->possible_duplicate_ids)->toBe([$existing->id]);
});

it('walks through the add form: check duplicates, confirm, submit', function () {
    Business::factory()->for($this->city)->create(['whatsapp' => '0612345678', 'name' => 'Ancien']);

    $component = Livewire::actingAs($this->user)
        ->test(AddBusiness::class, ['city' => $this->city])
        ->set('name', 'Plomberie Atlas')
        ->set('categoryId', $this->plumbers->id)
        ->set('address', '12 rue de Fès')
        ->set('latitude', 33.574)
        ->set('longitude', -7.59)
        ->set('whatsapp', '06 12 34 56 78')
        ->call('check')
        ->assertHasNoErrors()
        ->assertSet('checked', true)
        ->assertSee('Ancien');

    $component->call('submit')->assertHasErrors('confirmedNotDuplicate');

    $component->set('confirmedNotDuplicate', true)->call('submit')->assertHasNoErrors()->assertSet('submittedName', 'Plomberie Atlas');

    expect(Business::query()->where('name', 'Plomberie Atlas')->sole()->status)->toBe(BusinessStatus::Pending);
});

it('refuses a position far away from the city', function () {
    Livewire::actingAs($this->user)
        ->test(AddBusiness::class, ['city' => $this->city])
        ->set('name', 'Loin')
        ->set('categoryId', $this->plumbers->id)
        ->set('address', 'Ailleurs')
        ->set('latitude', 31.63)   // Marrakech
        ->set('longitude', -8.0)
        ->call('check')
        ->assertHasErrors('latitude');
});

it('refuses category groups and unsafe websites in the form', function () {
    Livewire::actingAs($this->user)
        ->test(AddBusiness::class, ['city' => $this->city])
        ->set('name', 'Test')
        ->set('categoryId', $this->group->id)
        ->set('address', 'x')
        ->set('latitude', 33.574)
        ->set('longitude', -7.59)
        ->set('website', 'javascript:alert(1)')
        ->call('check')
        ->assertHasErrors(['categoryId', 'website']);
});

it('sends guests to the login page', function () {
    $this->get('/fr/ma-ville/ajouter')->assertRedirect('/fr/login');
});
