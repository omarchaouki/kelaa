<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Page;

it('strips scripts, event handlers and dangerous links from page HTML', function () {
    $page = Page::factory()->create([
        'slug' => 'about-test',
        'body' => [
            'fr' => '<p onclick="steal()">Bonjour <strong>Maroc</strong></p><script>alert(1)</script>'
                .'<a href="javascript:alert(2)">x</a><a href="https://example.ma">ok</a>'
                .'<img src=x onerror=alert(3)><iframe src="https://evil.example"></iframe>',
        ],
    ]);

    $stored = $page->fresh()->getTranslation('body', 'fr');

    expect($stored)->toContain('<strong>Maroc</strong>', 'href="https://example.ma"', 'rel="noopener noreferrer nofollow"')
        ->not->toContain('<script', 'onclick', 'javascript:', 'onerror', '<iframe', '<img');
});

it('renders sanitized HTML even if unsafe HTML reached the database', function () {
    City::factory()->create();
    $page = Page::factory()->create(['slug' => 'legacy']);
    // Simulate a row written without the model (e.g. a raw SQL import).
    Page::query()->whereKey($page->id)->toBase()->update(['body' => json_encode(['fr' => '<p>ok</p><script>alert(1)</script>'])]);

    $this->get('/fr/p/legacy')->assertOk()->assertSee('<p>ok</p>', escape: false)->assertDontSee('<script>alert(1)', escape: false);
});

it('normalizes business contact data and drops unsafe links', function () {
    $business = Business::factory()->create([
        'phone' => '05 22 12 34 56',
        'whatsapp' => '06 12 34 56 78',
        'website' => 'javascript:alert(1)',
        'email' => 'not-an-email',
        'social_links' => [
            'facebook' => 'facebook.com/garage',
            'instagram' => 'javascript:alert(1)',
            'myspace' => 'https://myspace.example',
        ],
    ]);

    $business->refresh();

    expect($business->phone)->toBe('+212522123456')
        ->and($business->whatsapp)->toBe('+212612345678')
        ->and($business->website)->toBeNull()
        ->and($business->email)->toBeNull()
        ->and($business->social_links)->toBe(['facebook' => 'https://facebook.com/garage']);
});

it('refuses city and category slugs that would shadow application routes', function (string $model, string $slug) {
    $model::factory()->create(['slug' => $slug]);
})->with([
    [City::class, 'admin'],
    [City::class, 'login'],
    [City::class, 'ar'],
    [Category::class, 'b'],
    [Category::class, 'recherche'],
    [Category::class, 'pharmacies-de-garde'],
])->throws(DomainException::class);
