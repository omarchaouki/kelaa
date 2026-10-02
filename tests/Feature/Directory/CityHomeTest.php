<?php

use App\Livewire\Directory\CityHome;
use App\Livewire\Directory\QuickSearch;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Review;
use App\Models\SearchSynonym;
use App\Services\Directory\CityDirectory;
use Livewire\Livewire;

beforeEach(function () {
    $this->city = City::factory()->create(['slug' => 'ma-ville']);
    $this->auto = Category::factory()->create(['slug' => 'auto', 'name' => ['fr' => 'Auto', 'ar' => 'السيارات', 'en' => 'Cars']]);
    $this->mechanics = Category::factory()->childOf($this->auto)->create(['slug' => 'mecanicien', 'name' => ['fr' => 'Mécaniciens', 'ar' => 'الميكانيك', 'en' => 'Mechanics']]);
    $this->lawyers = Category::factory()->childOf(Category::factory()->create())->create(['slug' => 'avocats', 'name' => ['fr' => 'Avocats', 'ar' => 'المحامون', 'en' => 'Lawyers']]);
});

it('shows every category with its number of listings and the city figures', function () {
    $garages = Business::factory()->count(3)->for($this->city)->inCategory($this->mechanics)->create();
    Review::factory()->for($garages[0])->count(2)->create();

    $this->get('/fr/ma-ville')
        ->assertOk()
        ->assertSee('Mécaniciens')
        ->assertSee('3 adresses')
        ->assertSee('Avocats')
        ->assertSee('Bientôt')            // a category without listings yet
        ->assertSee('Les plus demandés')
        ->assertSee('Ajouter mon établissement')
        ->assertSeeLivewire(QuickSearch::class);

    expect(app(CityDirectory::class)->stats($this->city))
        ->toMatchArray(['businesses' => 3, 'reviews' => 2]);
});

it('lets the browser filter categories by any language or Darija synonym', function () {
    SearchSynonym::query()->create(['term' => 'mécanicien', 'locale' => null, 'synonyms' => ['mikanik', 'ميكانيكي']]);

    $index = collect(app(CityDirectory::class)->categorySearchIndex(app(CityDirectory::class)->categoryTree($this->city)['groups']));
    $mechanics = $index->firstWhere('id', $this->mechanics->id);

    expect($mechanics['group'])->toBe($this->auto->id)
        ->and($mechanics['text'])->toContain('mécaniciens', 'mechanics', 'الميكانيك', 'mikanik', 'auto')
        ->and($index->firstWhere('id', $this->lawyers->id)['text'])->not->toContain('mikanik');
});

it('suggests businesses by name while typing', function () {
    Business::factory()->for($this->city)->inCategory($this->mechanics)->create(['name' => 'Garage Atlas']);

    Livewire::test(QuickSearch::class, ['city' => $this->city])
        ->set('q', 'Atlas')
        ->assertSee('Garage Atlas')
        ->call('search')
        ->assertRedirect(route('city.search', ['city' => $this->city, 'q' => 'Atlas']));
});

it('keeps the location shortcut on the home page', function () {
    Livewire::test(CityHome::class, ['city' => $this->city])->assertSee('Près de moi');
});
