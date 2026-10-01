<?php

use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\Review;
use App\Services\Ranking\RankingService;
use App\Support\Seo\JsonLd;

beforeEach(function () {
    $this->city = City::factory()->create(['slug' => 'ma-ville', 'name' => 'Ma Ville']);
    $this->parent = Category::factory()->create(['slug' => 'sante', 'schema_type' => null]);
    $this->category = Category::factory()->childOf($this->parent)->create(['slug' => 'pharmacies', 'schema_type' => 'Pharmacy']);
    $this->business = Business::factory()->for($this->city)->inCategory($this->category)->create([
        'name' => 'Pharmacie Atlas',
        'address' => '12, Rue des Fleurs',
        'phone' => '+212522000000',
        'price_range' => 2,
        'latitude' => 33.5731,
        'longitude' => -7.5898,
    ]);
    BusinessHour::query()->create(['business_id' => $this->business->id, 'day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '13:00', 'is_closed' => false]);
    BusinessHour::query()->create(['business_id' => $this->business->id, 'day_of_week' => 1, 'opens_at' => '15:00', 'closes_at' => '20:00', 'is_closed' => false]);
});

/**
 * @return list<array<string, mixed>>
 */
function jsonLdBlocks(string $html): array
{
    preg_match_all('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $matches);

    return array_map(fn (string $json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
}

it('describes a business as its schema.org subtype with address, geo, hours and rating', function () {
    Review::factory()->for($this->business)->count(2)->create(['rating' => 4]);
    app(RankingService::class)->recalculate($this->business->fresh());

    $html = $this->get('/fr/ma-ville/b/'.$this->business->slug)->assertOk()->getContent();
    [$business, $breadcrumbs] = jsonLdBlocks($html);

    expect($business['@context'])->toBe('https://schema.org')
        ->and($business['@type'])->toBe('Pharmacy')
        ->and($business['name'])->toBe('Pharmacie Atlas')
        ->and($business['url'])->toBe(route('city.business', ['locale' => 'fr', 'city' => 'ma-ville', 'business' => $this->business->slug]))
        ->and($business['address'])->toMatchArray(['@type' => 'PostalAddress', 'streetAddress' => '12, Rue des Fleurs', 'addressLocality' => 'Ma Ville', 'addressCountry' => 'MA'])
        ->and($business['geo'])->toBe(['@type' => 'GeoCoordinates', 'latitude' => 33.5731, 'longitude' => -7.5898])
        ->and($business['telephone'])->toBe('+212522000000')
        ->and($business['priceRange'])->toBe('$$')
        ->and($business['openingHoursSpecification'])->toBe([
            ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Monday', 'opens' => '09:00', 'closes' => '13:00'],
            ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Monday', 'opens' => '15:00', 'closes' => '20:00'],
        ])
        ->and($business['aggregateRating'])->toBe(['@type' => 'AggregateRating', 'ratingValue' => 4, 'reviewCount' => 2, 'bestRating' => 5, 'worstRating' => 1]);

    expect($breadcrumbs['@type'])->toBe('BreadcrumbList')
        ->and(array_column($breadcrumbs['itemListElement'], 'name'))->toBe(['Ma Ville', $this->category->name, 'Pharmacie Atlas'])
        ->and(array_column($breadcrumbs['itemListElement'], 'position'))->toBe([1, 2, 3]);
});

it('leaves the rating out when there is no review, and falls back to LocalBusiness', function () {
    $plain = Category::factory()->create(['schema_type' => 'NotAType']);
    $business = Business::factory()->for($this->city)->inCategory($plain)->create();

    [$data] = jsonLdBlocks($this->get('/fr/ma-ville/b/'.$business->slug)->getContent());

    expect($data['@type'])->toBe('LocalBusiness')
        ->and($data)->not->toHaveKey('aggregateRating');
});

it('cannot be broken out of with a malicious business name', function () {
    $this->business->forceFill(['name' => 'Evil</script><script>alert(1)</script>', 'description' => '"><img src=x onerror=alert(1)>'])->save();

    $html = $this->get('/fr/ma-ville/b/'.$this->business->slug)->assertOk()->getContent();

    expect($html)->not->toContain('</script><script>alert(1)')
        ->and(jsonLdBlocks($html)[0]['name'])->toBe('Evil</script><script>alert(1)</script>')
        ->and(JsonLd::encode(['x' => '</script>']))->not->toContain('<')
        ->and(json_decode(JsonLd::encode(['x' => '</script>']), true))->toBe(['x' => '</script>']);
});

it('carries the CSP nonce on structured data blocks', function () {
    $html = $this->get('/fr/ma-ville/b/'.$this->business->slug)->getContent();

    expect($html)->toMatch('#<script type="application/ld\+json" nonce="[^"]+">#');
});

it('adds Open Graph, canonical and hreflang tags', function () {
    $this->get('/fr/ma-ville/b/'.$this->business->slug)
        ->assertSee('<meta property="og:type" content="business.business">', false)
        ->assertSee('<meta property="og:url" content="'.route('city.business', ['locale' => 'fr', 'city' => 'ma-ville', 'business' => $this->business->slug]).'">', false)
        ->assertSee('<meta property="og:description"', false)
        ->assertSee('<link rel="canonical"', false)
        ->assertSee('hreflang="ar"', false)
        ->assertSee('hreflang="x-default"', false)
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false);
});

it('adds breadcrumbs to category pages and a search action to the city home', function () {
    $category = jsonLdBlocks($this->get('/fr/ma-ville/pharmacies')->assertOk()->getContent());
    $home = jsonLdBlocks($this->get('/fr/ma-ville')->assertOk()->getContent());

    expect($category[0]['@type'])->toBe('BreadcrumbList')
        ->and(count($category[0]['itemListElement']))->toBe(3)
        ->and($home[0]['@type'])->toBe('WebSite')
        ->and($home[0]['potentialAction']['target'])->toEndWith('/fr/ma-ville/recherche?q={search_term_string}');
});

it('points the canonical of a neighborhood page at its path, not a query string', function () {
    $neighborhood = Neighborhood::factory()->for($this->city)->create(['slug' => 'maarif']);
    $this->business->forceFill(['neighborhood_id' => $neighborhood->id])->save();

    $this->get('/fr/ma-ville/pharmacies/maarif')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/fr/ma-ville/pharmacies/maarif').'">', false);
});

it('serves robots.txt that blocks everything outside production', function () {
    $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->assertSeeText("Disallow: /\n", false);
});

it('serves a production robots.txt pointing to the sitemap', function () {
    app()->detectEnvironment(fn () => 'production');

    $body = $this->get('/robots.txt')->getContent();

    expect($body)->toContain('Disallow: /admin')
        ->and($body)->toContain('Disallow: /fr/dashboard')
        ->and($body)->toContain('Sitemap: '.url('sitemap.xml'))
        ->and($body)->not->toContain("Disallow: /\n");
});

it('serves the web app manifest and the PWA files', function () {
    $this->get('/manifest.webmanifest')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJsonPath('display', 'standalone')
        ->assertJsonPath('icons.2.purpose', 'maskable');

    foreach (['sw.js', 'offline.html', 'icons/icon-192.png', 'icons/icon-512.png', 'icons/icon-maskable-512.png', 'icons/apple-touch-icon.png'] as $file) {
        expect(file_exists(public_path($file)))->toBeTrue("Missing public/{$file}");
    }

    // The service worker never caches pages (they may contain personal data).
    $sw = file_get_contents(public_path('sw.js'));
    expect(substr_count($sw, 'cache.put'))->toBe(1)
        ->and($sw)->toContain('fetch(request).catch(() => caches.match(OFFLINE_URL))');
});

it('shows the cookie banner with refuse as easy as accept', function () {
    $this->get('/fr/ma-ville')
        ->assertSee('x-data="cookieConsent"', false)
        ->assertSee('Refuser')
        ->assertSee('Accepter')
        ->assertSee('Gérer les cookies');
});
