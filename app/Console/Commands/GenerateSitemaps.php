<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Page;
use App\Support\Locales;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

/**
 * Split sitemaps (static pages, categories and businesses per city), each URL
 * listed once per locale with its hreflang alternates. Only public, indexable
 * pages: published businesses of active cities, non-empty category pages.
 */
#[Signature('sitemap:generate {--path= : Output directory, public/ by default}')]
#[Description('Write public/sitemap.xml and the split sitemaps in public/sitemaps')]
class GenerateSitemaps extends Command
{
    /** Businesses per file (x 3 locales stays well under the 50,000 URL limit). */
    public const CHUNK = 5000;

    /** A category page restricted to a neighborhood is listed from this many businesses. */
    public const MIN_NEIGHBORHOOD_RESULTS = 3;

    private string $directory;

    /** @var list<string> */
    private array $files = [];

    public function handle(): int
    {
        $base = rtrim((string) ($this->option('path') ?: public_path()), '/\\');
        $this->directory = $base.'/sitemaps';
        $staging = $this->directory.'-tmp';
        File::deleteDirectory($staging);
        File::ensureDirectoryExists($staging);

        $this->write($staging, 'static.xml', $this->staticSitemap());

        City::query()->active()->orderBy('id')->each(function (City $city) use ($staging): void {
            $this->write($staging, "categories-{$city->slug}.xml", $this->categorySitemap($city));

            Business::query()->published()->where('city_id', $city->id)
                ->select(['id', 'city_id', 'slug', 'updated_at'])
                ->orderBy('id')
                ->chunkById(self::CHUNK, function ($businesses, int $page) use ($city, $staging): void {
                    $sitemap = Sitemap::create();

                    foreach ($businesses as $business) {
                        $this->addLocalized($sitemap, fn (string $locale) => route('city.business', ['locale' => $locale, 'city' => $city->slug, 'business' => $business->slug]), $business->updated_at);
                    }

                    $this->write($staging, "businesses-{$city->slug}-{$page}.xml", $sitemap);
                });
        });

        // Swap in the new set at once: crawlers never see a half-written index.
        File::deleteDirectory($this->directory);
        File::moveDirectory($staging, $this->directory);

        $index = SitemapIndex::create();

        foreach ($this->files as $file) {
            $index->add(url('sitemaps/'.$file));
        }

        $index->writeToFile($base.'/sitemap.xml');

        $this->info(count($this->files).' sitemap files written.');

        return self::SUCCESS;
    }

    private function staticSitemap(): Sitemap
    {
        $sitemap = Sitemap::create();

        City::query()->active()->orderBy('id')->each(function (City $city) use ($sitemap): void {
            $this->addLocalized($sitemap, fn (string $locale) => route('city.home', ['locale' => $locale, 'city' => $city->slug]));
            $this->addLocalized($sitemap, fn (string $locale) => route('city.pharmacies', ['locale' => $locale, 'city' => $city->slug]));
        });

        Page::query()->where('is_published', true)->orderBy('id')->each(function (Page $page) use ($sitemap): void {
            $this->addLocalized($sitemap, fn (string $locale) => route('page.show', ['locale' => $locale, 'page' => $page->slug]), $page->updated_at);
        });

        return $sitemap;
    }

    private function categorySitemap(City $city): Sitemap
    {
        $sitemap = Sitemap::create();

        $counts = DB::table('business_category')
            ->join('businesses', 'businesses.id', '=', 'business_category.business_id')
            ->where('businesses.city_id', $city->id)
            ->where('businesses.status', 'published')
            ->whereNull('businesses.deleted_at')
            ->groupBy('business_category.category_id')
            ->pluck(DB::raw('count(*)'), 'business_category.category_id');

        $categories = Category::query()->where('is_active', true)->with('parent')->orderBy('id')->get();

        foreach ($categories as $category) {
            // A parent category page lists its children's businesses too.
            $total = (int) ($counts[$category->id] ?? 0)
                + $categories->where('parent_id', $category->id)->sum(fn (Category $child) => (int) ($counts[$child->id] ?? 0));

            if ($total === 0) {
                continue;
            }

            $this->addLocalized($sitemap, fn (string $locale) => route('city.category', ['locale' => $locale, 'city' => $city->slug, 'category' => $category->slug]));
        }

        $neighborhoodPages = DB::table('business_category')
            ->join('businesses', 'businesses.id', '=', 'business_category.business_id')
            ->join('neighborhoods', 'neighborhoods.id', '=', 'businesses.neighborhood_id')
            ->join('categories', 'categories.id', '=', 'business_category.category_id')
            ->where('businesses.city_id', $city->id)
            ->where('businesses.status', 'published')
            ->whereNull('businesses.deleted_at')
            ->where('neighborhoods.is_active', true)
            ->where('categories.is_active', true)
            ->groupBy('categories.slug', 'neighborhoods.slug')
            ->havingRaw('count(*) >= ?', [self::MIN_NEIGHBORHOOD_RESULTS])
            ->orderBy('categories.slug')
            ->orderBy('neighborhoods.slug')
            ->get(['categories.slug as category', 'neighborhoods.slug as neighborhood']);

        foreach ($neighborhoodPages as $row) {
            $this->addLocalized($sitemap, fn (string $locale) => route('city.category', [
                'locale' => $locale, 'city' => $city->slug, 'category' => $row->category, 'neighborhoodSlug' => $row->neighborhood,
            ]));
        }

        return $sitemap;
    }

    /**
     * One <url> per locale, each pointing at all its translations.
     *
     * @param  callable(string): string  $url
     */
    private function addLocalized(Sitemap $sitemap, callable $url, ?CarbonInterface $lastModified = null): void
    {
        $alternates = [];

        foreach (Locales::supported() as $locale) {
            $alternates[$locale] = $url($locale);
        }

        foreach ($alternates as $href) {
            $tag = Url::create($href);

            if ($lastModified !== null) {
                $tag->setLastModificationDate($lastModified);
            }

            foreach ($alternates as $locale => $alternate) {
                $tag->addAlternate($alternate, $locale);
            }

            $sitemap->add($tag);
        }
    }

    private function write(string $directory, string $name, Sitemap $sitemap): void
    {
        $sitemap->writeToFile($directory.'/'.$name);
        $this->files[] = $name;
    }
}
