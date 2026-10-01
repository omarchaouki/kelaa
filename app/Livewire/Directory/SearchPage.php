<?php

namespace App\Livewire\Directory;

use App\Enums\SearchSort;
use App\Livewire\Concerns\WithVisitorLocation;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\SponsoredPlacement;
use App\Services\Hours\OpeningHours;
use App\Services\Hours\OpeningStatus;
use App\Services\Search\BusinessSearch;
use App\Services\Search\SearchFilters;
use App\Services\Sponsoring\SponsoredSlots;
use App\Services\Tracking\ContactEventRecorder;
use App\Support\Seo\JsonLd;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Category listing (/{city}/{category}[/{neighborhood}]) and free search
 * (/{city}/recherche). Every URL parameter is untrusted: values are read
 * through SearchFilters, which drops anything outside its whitelists.
 */
#[Layout('layouts.app')]
class SearchPage extends Component
{
    use WithPagination, WithVisitorLocation;

    public const PER_PAGE = 20;

    #[Locked]
    public City $city;

    #[Locked]
    public ?int $categoryId = null;

    #[Locked]
    public ?int $pathNeighborhoodId = null;

    #[Url(except: '')]
    public string $q = '';

    #[Url(as: 'quartier', except: '')]
    public string $neighborhood = '';

    #[Url(except: '')]
    public string $open = '';

    #[Url(except: '')]
    public string $rating = '';

    /** Comma separated price levels, e.g. "1,2". */
    #[Url(except: '')]
    public string $price = '';

    #[Url(except: '')]
    public string $verified = '';

    #[Url(except: '')]
    public string $radius = '';

    #[Url(except: 'relevance')]
    public string $sort = 'relevance';

    #[Url(as: 'vue', except: 'list')]
    public string $view = 'list';

    public function mount(City $city, ?Category $category = null, ?string $neighborhoodSlug = null): void
    {
        abort_unless($city->is_active, 404);
        abort_if($category !== null && ! $category->is_active, 404);

        $this->city = $city;
        $this->categoryId = $category?->id;

        if ($neighborhoodSlug !== null) {
            $this->pathNeighborhoodId = Neighborhood::query()
                ->where('city_id', $city->id)
                ->where('slug', $neighborhoodSlug)
                ->where('is_active', true)
                ->firstOrFail()
                ->id;
        }
    }

    public function updated(string $property): void
    {
        if ($property !== 'view') {
            $this->resetPage();
        }
    }

    public function toggle(string $filter): void
    {
        if (in_array($filter, ['open', 'verified'], true)) {
            $this->{$filter} = $this->{$filter} === '' ? '1' : '';
            $this->resetPage();
        }
    }

    public function togglePrice(int $level): void
    {
        $levels = SearchFilters::fromInput(['price' => $this->price])->priceRanges;

        if ($level >= 1 && $level <= 4) {
            $levels = in_array($level, $levels, true) ? array_diff($levels, [$level]) : [...$levels, $level];
            sort($levels);
        }

        $this->price = implode(',', $levels);
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('q', 'neighborhood', 'open', 'rating', 'price', 'verified', 'radius', 'sort');
        $this->resetPage();
    }

    protected function locationChanged(): void
    {
        $this->resetPage();
    }

    protected function filters(): SearchFilters
    {
        return SearchFilters::fromInput([
            'q' => $this->q,
            'open' => $this->open,
            'rating' => $this->rating,
            'price' => $this->price,
            'verified' => $this->verified,
            'radius' => $this->radius,
            'sort' => $this->sort,
        ], $this->categoryId, $this->neighborhoodId());
    }

    protected function neighborhoodId(): ?int
    {
        if ($this->pathNeighborhoodId !== null) {
            return $this->pathNeighborhoodId;
        }

        if ($this->neighborhood === '') {
            return null;
        }

        return Neighborhood::query()->where('city_id', $this->city->id)->where('slug', $this->neighborhood)->value('id');
    }

    public function render(BusinessSearch $search, SponsoredSlots $slots, ContactEventRecorder $recorder): View
    {
        $filters = $this->filters();
        $location = $this->location();
        $category = $this->categoryId ? Category::query()->with('parent')->find($this->categoryId) : null;
        $neighborhoods = Neighborhood::query()->where('city_id', $this->city->id)->where('is_active', true)->orderBy('slug')->get();
        $neighborhood = $neighborhoods->firstWhere('id', $filters->neighborhoodId);
        $page = $this->getPage();

        /** @var Collection<int, SponsoredPlacement> $sponsored */
        $sponsored = $category !== null ? $slots->for($this->city, $category, $neighborhood) : collect();

        $results = $search->search(
            $this->city,
            $filters,
            $location,
            self::PER_PAGE,
            $page,
            $sponsored->pluck('business_id')->map(fn ($id) => (int) $id)->all(),
        );

        if ($page === 1) {
            $recorder->recordImpressions($sponsored, request());
        }

        $statuses = collect([...$sponsored->pluck('business'), ...$results->items()])
            ->mapWithKeys(fn (Business $business) => [$business->id => OpeningHours::for($business)->statusAt()]);

        return view('livewire.directory.search-page', [
            'category' => $category,
            'neighborhood' => $neighborhood,
            'neighborhoods' => $neighborhoods,
            'sponsored' => $sponsored,
            'results' => $results,
            'statuses' => $statuses,
            'hasLocation' => $location !== null,
            'filters' => $filters,
            'sorts' => SearchSort::cases(),
            'markers' => $this->view === 'map' ? $this->markers($sponsored, $results->items(), $statuses) : [],
        ])
            ->title($this->title($category, $neighborhood, $filters))
            ->layoutData([
                'metaDescription' => $this->metaDescription($category, $neighborhood),
                'canonical' => $this->canonicalUrl($category, $neighborhood, $page),
                // Filtered and free-text result pages are not indexed; category pages are.
                'noindex' => $category === null || $this->hasRefinements(),
                'structuredData' => $category === null ? [] : [JsonLd::breadcrumbs($this->breadcrumbs($category, $neighborhood))],
            ]);
    }

    protected function hasRefinements(): bool
    {
        return $this->q !== '' || $this->open !== '' || $this->rating !== '' || $this->price !== ''
            || $this->verified !== '' || $this->radius !== '' || $this->sort !== 'relevance'
            || ($this->neighborhood !== '' && $this->pathNeighborhoodId === null);
    }

    protected function title(?Category $category, ?Neighborhood $neighborhood, SearchFilters $filters): string
    {
        if ($category !== null && $neighborhood !== null) {
            return __(':category à :neighborhood, :city', ['category' => $category->name, 'neighborhood' => $neighborhood->name, 'city' => $this->city->name]);
        }

        if ($category !== null) {
            return __(':category à :city', ['category' => $category->name, 'city' => $this->city->name]);
        }

        return $filters->hasText()
            ? __('« :query » à :city', ['query' => $filters->query, 'city' => $this->city->name])
            : __('Rechercher à :city', ['city' => $this->city->name]);
    }

    protected function metaDescription(?Category $category, ?Neighborhood $neighborhood): ?string
    {
        if ($category === null) {
            return null;
        }

        return __('Les meilleurs :category à :place : avis, horaires, téléphone et WhatsApp.', [
            'category' => mb_strtolower($category->name),
            'place' => $neighborhood ? $neighborhood->name.', '.$this->city->name : $this->city->name,
        ]);
    }

    /**
     * @return list<array{name: string, url: string}>
     */
    protected function breadcrumbs(Category $category, ?Neighborhood $neighborhood): array
    {
        $items = [['name' => $this->city->name, 'url' => route('city.home', ['city' => $this->city])]];

        if ($category->parent !== null) {
            $items[] = ['name' => $category->parent->name, 'url' => route('city.category', ['city' => $this->city, 'category' => $category->parent])];
        }

        $items[] = ['name' => $category->name, 'url' => route('city.category', ['city' => $this->city, 'category' => $category])];

        if ($neighborhood !== null) {
            $items[] = ['name' => $neighborhood->name, 'url' => $this->canonicalUrl($category, $neighborhood, 1)];
        }

        return $items;
    }

    protected function canonicalUrl(?Category $category, ?Neighborhood $neighborhood, int $page): string
    {
        if ($category === null) {
            return route('city.search', ['city' => $this->city]);
        }

        $parameters = ['city' => $this->city, 'category' => $category];

        if ($neighborhood !== null) {
            $parameters['neighborhoodSlug'] = $neighborhood->slug;
        }

        if ($page > 1) {
            $parameters['page'] = $page;
        }

        return route('city.category', $parameters);
    }

    /**
     * @param  Collection<int, SponsoredPlacement>  $sponsored
     * @param  array<int, Business>  $organic
     * @param  Collection<int, OpeningStatus>  $statuses
     * @return list<array<string, mixed>>
     */
    protected function markers(Collection $sponsored, array $organic, Collection $statuses): array
    {
        $markers = [];

        foreach ($sponsored as $placement) {
            $markers[] = $this->marker($placement->business, $statuses, $placement);
        }

        foreach ($organic as $business) {
            $markers[] = $this->marker($business, $statuses);
        }

        return $markers;
    }

    /**
     * @param  Collection<int, OpeningStatus>  $statuses
     * @return array<string, mixed>
     */
    protected function marker(Business $business, Collection $statuses, ?SponsoredPlacement $placement = null): array
    {
        return [
            'name' => $business->name,
            'lat' => $business->latitude,
            'lng' => $business->longitude,
            'url' => route('city.business', array_filter([
                'city' => $this->city,
                'business' => $business,
                'src' => $placement ? 'sponsored' : 'map',
                'pl' => $placement?->id,
            ])),
            'sponsored' => $placement !== null,
            'status' => $statuses[$business->id]?->label(),
        ];
    }
}
