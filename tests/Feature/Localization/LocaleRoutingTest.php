<?php

use App\Models\City;

beforeEach(function () {
    config(['directory.default_city' => 'ma-ville']);
    $this->city = City::factory()->create(['slug' => 'ma-ville', 'name' => ['fr' => 'Ma Ville', 'ar' => 'مدينتي', 'en' => 'My City']]);
});

it('redirects the root URL to the browser language', function () {
    $this->get('/', ['Accept-Language' => 'ar-MA,ar;q=0.9,fr;q=0.8'])->assertRedirect('/ar');
    $this->get('/', ['Accept-Language' => 'de-DE'])->assertRedirect('/fr');
});

it('prefers the remembered locale cookie over the browser language', function () {
    $this->withCookie('locale', 'en')
        ->get('/', ['Accept-Language' => 'ar'])
        ->assertRedirect('/en');
});

it('redirects a locale root to the default city', function () {
    $this->get('/fr')->assertRedirect('/fr/ma-ville');
});

it('returns 404 for unsupported locales and unknown or inactive cities', function () {
    $this->get('/de/ma-ville')->assertNotFound();
    $this->get('/fr/nowhere')->assertNotFound();

    $this->city->update(['is_active' => false]);
    $this->get('/fr/ma-ville')->assertNotFound();
});

it('renders Arabic pages right to left with Arabic content', function () {
    $this->get('/ar/ma-ville')
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl">', escape: false)
        ->assertSee('مدينتي');
});

it('renders French and English pages left to right', function (string $locale, string $name) {
    $this->get("/{$locale}/ma-ville")
        ->assertOk()
        ->assertSee("<html lang=\"{$locale}\" dir=\"ltr\">", escape: false)
        ->assertSee($name);
})->with([
    ['fr', 'Ma Ville'],
    ['en', 'My City'],
]);

it('links every language version with hreflang', function () {
    $this->get('/fr/ma-ville')
        ->assertSee('hreflang="fr" href="'.url('/fr/ma-ville').'"', escape: false)
        ->assertSee('hreflang="ar" href="'.url('/ar/ma-ville').'"', escape: false)
        ->assertSee('hreflang="en" href="'.url('/en/ma-ville').'"', escape: false)
        ->assertSee('hreflang="x-default" href="'.url('/fr/ma-ville').'"', escape: false);
});

it('remembers the locale of the last visited page', function () {
    $this->get('/ar/ma-ville')->assertCookie('locale', 'ar');
});

it('shows the CNDP number in the footer when configured', function () {
    config(['directory.cndp_number' => 'D-W-123/2026']);

    $this->get('/fr/ma-ville')->assertSee('D-W-123/2026');
});
