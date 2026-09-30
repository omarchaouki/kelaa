<?php

use App\Enums\ContactEventType;
use App\Livewire\Directory\CityHome;
use App\Livewire\Directory\SearchPage;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\ContactEvent;
use App\Models\Neighborhood;
use App\Models\PharmacyDuty;
use App\Models\Review;
use App\Models\SponsoredPlacement;
use App\Support\VisitorLocation;
use Livewire\Livewire;

beforeEach(function () {
    $this->city = City::factory()->create(['slug' => 'ma-ville', 'name' => ['fr' => 'Ma Ville', 'ar' => 'مدينتي', 'en' => 'My City']]);
    $this->group = Category::factory()->create(['slug' => 'maison', 'name' => ['fr' => 'Maison', 'ar' => 'المنزل', 'en' => 'Home']]);
    $this->plumbers = Category::factory()->childOf($this->group)->create(['slug' => 'plombier', 'name' => ['fr' => 'Plombier', 'ar' => 'سباك', 'en' => 'Plumber']]);
    $this->business = Business::factory()->for($this->city)->inCategory($this->plumbers)->withRegularHours()->create(['name' => 'Atelier Amine', 'slug' => 'atelier-amine']);
});

it('renders the city home in every locale', function (string $locale, string $expected) {
    $this->get("/{$locale}/ma-ville")->assertOk()->assertSee($expected)->assertSeeLivewire(CityHome::class);
})->with([
    ['fr', 'Plombier'],
    ['ar', 'سباك'],
    ['en', 'Plumber'],
]);

it('shows the pharmacies de garde of tonight on the home page', function () {
    $pharmacyCategory = Category::factory()->create(['slug' => 'pharmacie-de-garde']);
    $pharmacy = Business::factory()->for($this->city)->inCategory($pharmacyCategory)->create(['name' => 'Pharmacie Atlas']);
    PharmacyDuty::factory()->for($pharmacy)->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHours(8)]);

    $this->get('/fr/ma-ville')->assertSee('Pharmacie Atlas');
    $this->get('/fr/ma-ville/pharmacies-de-garde')->assertOk()->assertSee('Pharmacie Atlas');
});

it('lists a category and its businesses', function () {
    $this->get('/fr/ma-ville/plombier')->assertOk()->assertSee('Atelier Amine')->assertSeeLivewire(SearchPage::class);
    $this->get('/fr/ma-ville/maison')->assertOk()->assertSee('Atelier Amine');
});

it('narrows a category page to a neighborhood from the path', function () {
    $hood = Neighborhood::factory()->for($this->city)->create(['slug' => 'maarif']);
    Business::factory()->for($this->city)->inCategory($this->plumbers)->create(['name' => 'Plombier Maarif', 'neighborhood_id' => $hood->id]);

    $this->get('/fr/ma-ville/plombier/maarif')->assertOk()->assertSee('Plombier Maarif')->assertDontSee('Atelier Amine');
    $this->get('/fr/ma-ville/plombier/unknown')->assertNotFound();
});

it('returns 404 for unknown or inactive categories, cities and businesses', function () {
    $this->get('/fr/ma-ville/nope')->assertNotFound();
    $this->get('/fr/nowhere/plombier')->assertNotFound();
    $this->get('/fr/ma-ville/b/nope')->assertNotFound();

    $this->plumbers->update(['is_active' => false]);
    $this->get('/fr/ma-ville/plombier')->assertNotFound();
});

it('never shows a business of another city under this city', function () {
    $other = City::factory()->create(['slug' => 'autre']);
    $foreign = Business::factory()->for($other)->create(['slug' => 'foreign-shop']);

    $this->get('/fr/ma-ville/b/foreign-shop')->assertNotFound();
    $this->get('/fr/autre/b/foreign-shop')->assertOk();
});

it('hides unpublished businesses', function () {
    Business::factory()->for($this->city)->pending()->create(['slug' => 'pending-shop']);

    $this->get('/fr/ma-ville/b/pending-shop')->assertNotFound();
});

it('survives hostile query strings', function (string $query) {
    $this->get('/fr/ma-ville/plombier?'.$query)->assertOk();
})->with([
    'array q' => ['q[]=x'],
    'array filters' => ['price[]=1&open[]=1&verified[x]=1'],
    'bad sort' => ['sort=%27;DROP%20TABLE%20businesses;--'],
    'bad numbers' => ['rating=abc&radius=-5&page=-1'],
    'bad view' => ['vue=%3Cscript%3E'],
]);

it('escapes the search query in the page', function () {
    $this->get('/fr/ma-ville/recherche?q=%3Cscript%3Ealert(1)%3C/script%3E')
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', escape: false);
});

it('escapes business content everywhere', function () {
    $evil = Business::factory()->for($this->city)->inCategory($this->plumbers)->create([
        'name' => '<img src=x onerror=alert(1)>',
        'slug' => 'evil',
        'description' => ['fr' => '<script>alert(2)</script>'],
        'address' => '"><svg onload=alert(3)>',
    ]);
    Review::factory()->for($evil)->create(['body' => '<iframe src="https://evil.example"></iframe>']);

    foreach (['/fr/ma-ville/plombier', '/fr/ma-ville/b/evil', '/fr/ma-ville/plombier?vue=map'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->not->toContain('<img src=x onerror', '<script>alert(2)', '<svg onload', '<iframe src="https://evil');
    }
});

it('labels sponsored businesses and does not repeat them in the organic list', function () {
    $sponsored = Business::factory()->for($this->city)->inCategory($this->plumbers)->create(['name' => 'Plomberie Sponsor']);
    SponsoredPlacement::factory()->create([
        'business_id' => $sponsored->id, 'city_id' => $this->city->id, 'category_id' => $this->plumbers->id, 'position' => 1,
    ]);

    $html = $this->get('/fr/ma-ville/plombier')->assertOk()->assertSee('Sponsorisé')->getContent();
    expect(substr_count($html, 'Plomberie Sponsor</a>'))->toBe(1);

    $this->get('/ar/ma-ville/plombier')->assertSee('مُموَّل');
    $this->get('/en/ma-ville/plombier')->assertSee('Sponsored');
});

it('records a profile view and a sponsored click only for a real placement of that business', function () {
    $placement = SponsoredPlacement::factory()->create([
        'business_id' => $this->business->id, 'city_id' => $this->city->id, 'category_id' => $this->plumbers->id,
    ]);

    $this->withHeader('User-Agent', 'Mozilla/5.0 Chrome/130')
        ->get("/fr/ma-ville/b/atelier-amine?src=sponsored&pl={$placement->id}")->assertOk();

    $event = ContactEvent::query()->sole();
    expect($event->type)->toBe(ContactEventType::ProfileView)
        ->and($event->sponsored_placement_id)->toBe($placement->id)
        ->and($placement->fresh()->clicks)->toBe(1);
});

it('offers a WhatsApp link with a prefilled message', function () {
    $this->business->update(['whatsapp' => '0612345678']);

    $this->get('/fr/ma-ville/b/atelier-amine')
        ->assertSee('https://wa.me/212612345678?text=', escape: false)
        ->assertSee('tel:'.$this->business->fresh()->phone, escape: false);
});

it('keeps the visitor location out of URLs and rounds it', function () {
    Livewire::test(CityHome::class, ['city' => $this->city])
        ->call('setLocation', 33.573123, -7.589876)
        ->assertHasNoErrors();

    $location = VisitorLocation::get();
    expect($location->latitude)->toBe(33.573)->and($location->longitude)->toBe(-7.59);

    Livewire::test(CityHome::class, ['city' => $this->city])
        ->call('setLocation', 'abc', 999)
        ->assertHasErrors('location');
});

it('filters through URL parameters on the listing', function () {
    Business::factory()->for($this->city)->inCategory($this->plumbers)->create(['name' => 'Verified Plumber', 'is_verified' => true]);

    $this->get('/fr/ma-ville/plombier?verified=1')->assertSee('Verified Plumber')->assertDontSee('Atelier Amine');
});

it('marks filtered result pages as noindex and category pages as indexable', function () {
    $this->get('/fr/ma-ville/plombier')->assertDontSee('noindex', escape: false);
    $this->get('/fr/ma-ville/plombier?open=1')->assertSee('<meta name="robots" content="noindex, follow">', escape: false);
    $this->get('/fr/ma-ville/recherche?q=plombier')->assertSee('noindex', escape: false);
});
