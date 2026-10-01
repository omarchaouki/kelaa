<?php

use App\Console\Commands\GenerateSitemaps;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\Page;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->out = storage_path('framework/testing/sitemap-'.uniqid());
    File::ensureDirectoryExists($this->out);

    $this->city = City::factory()->create(['slug' => 'ma-ville']);
    $this->category = Category::factory()->create(['slug' => 'plombiers']);
    $this->empty = Category::factory()->create(['slug' => 'vide']);
    $this->neighborhood = Neighborhood::factory()->for($this->city)->create(['slug' => 'maarif']);
});

afterEach(fn () => File::deleteDirectory($this->out));

function sitemapUrls(string $file): array
{
    $urls = [];

    foreach (simplexml_load_file($file)->children() as $node) {
        $urls[] = (string) $node->loc;
    }

    return $urls;
}

it('writes an index and split sitemaps with only public pages', function () {
    $published = Business::factory()->count(3)->for($this->city)->inCategory($this->category)->create(['neighborhood_id' => $this->neighborhood->id]);
    $pending = Business::factory()->for($this->city)->inCategory($this->category)->pending()->create();
    $inactiveCity = City::factory()->create(['slug' => 'fermee', 'is_active' => false]);
    Business::factory()->for($inactiveCity)->inCategory($this->category)->create();
    Page::query()->create(['slug' => 'cgu', 'title' => ['fr' => 'CGU'], 'body' => ['fr' => '<p>x</p>'], 'is_published' => true]);
    Page::query()->create(['slug' => 'brouillon', 'title' => ['fr' => 'B'], 'body' => ['fr' => '<p>x</p>'], 'is_published' => false]);

    $this->artisan('sitemap:generate', ['--path' => $this->out])->assertSuccessful();

    $index = sitemapUrls($this->out.'/sitemap.xml');
    expect($index)->toBe([
        url('sitemaps/static.xml'),
        url('sitemaps/categories-ma-ville.xml'),
        url('sitemaps/businesses-ma-ville-1.xml'),
    ]);

    $businesses = sitemapUrls($this->out.'/sitemaps/businesses-ma-ville-1.xml');
    expect($businesses)->toHaveCount(9)  // 3 businesses x 3 locales
        ->and($businesses)->toContain(url("/ar/ma-ville/b/{$published[0]->slug}"))
        ->and(implode(' ', $businesses))->not->toContain($pending->slug);

    $categories = sitemapUrls($this->out.'/sitemaps/categories-ma-ville.xml');
    expect($categories)->toContain(url('/fr/ma-ville/plombiers'))
        ->and($categories)->toContain(url('/en/ma-ville/plombiers/maarif'))  // 3 businesses in the neighborhood
        ->and(implode(' ', $categories))->not->toContain('/vide');

    $static = sitemapUrls($this->out.'/sitemaps/static.xml');
    expect($static)->toContain(url('/fr/ma-ville'))
        ->and($static)->toContain(url('/fr/p/cgu'))
        ->and(implode(' ', $static))->not->toContain('brouillon')
        ->and(implode(' ', $static))->not->toContain('fermee');
});

it('lists every translation as an hreflang alternate', function () {
    Business::factory()->for($this->city)->inCategory($this->category)->create(['slug' => 'garage-atlas']);

    $this->artisan('sitemap:generate', ['--path' => $this->out])->assertSuccessful();
    $xml = file_get_contents($this->out.'/sitemaps/businesses-ma-ville-1.xml');

    expect($xml)->toContain('hreflang="ar" href="'.url('/ar/ma-ville/b/garage-atlas').'"')
        ->and($xml)->toContain('hreflang="en" href="'.url('/en/ma-ville/b/garage-atlas').'"')
        ->and($xml)->toContain('<lastmod>');
});

it('splits businesses into several files and removes stale ones', function () {
    File::ensureDirectoryExists($this->out.'/sitemaps');
    File::put($this->out.'/sitemaps/businesses-ancienne-ville-1.xml', 'stale');

    Business::factory()->count(3)->for($this->city)->inCategory($this->category)->create();
    $this->artisan('sitemap:generate', ['--path' => $this->out])->assertSuccessful();

    expect(File::exists($this->out.'/sitemaps/businesses-ancienne-ville-1.xml'))->toBeFalse()
        ->and(GenerateSitemaps::CHUNK * 3)->toBeLessThan(50000);
});

it('is scheduled daily', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains((string) $event->command, 'sitemap:generate'));

    expect($events)->toHaveCount(1);
});
