<?php

namespace App\Livewire\Directory;

use App\Livewire\Concerns\WithVisitorLocation;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Services\Directory\CityDirectory;
use App\Services\Search\BusinessSearch;
use App\Services\Search\SearchFilters;
use App\Support\Seo\JsonLd;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class CityHome extends Component
{
    use WithVisitorLocation;

    #[Locked]
    public City $city;

    public string $q = '';

    public function mount(City $city): void
    {
        abort_unless($city->is_active, 404);

        $this->city = $city;
    }

    public function search(): mixed
    {
        return $this->redirectRoute('city.search', ['city' => $this->city, 'q' => SearchFilters::cleanQuery($this->q) ?: null], navigate: true);
    }

    /**
     * Autocomplete: matching categories first, then businesses.
     *
     * @return array{categories: Collection<int, Category>, businesses: Collection<int, Business>}
     */
    protected function suggestions(CityDirectory $directory, BusinessSearch $search): array
    {
        $query = SearchFilters::cleanQuery($this->q);

        if (mb_strlen($query) < 2) {
            return ['categories' => collect(), 'businesses' => collect()];
        }

        $needle = mb_strtolower($query);

        $categories = $directory->categoryTree($this->city)['groups']
            ->flatMap(fn (Category $group) => [$group, ...$group->children])
            ->filter(fn (Category $category) => collect($category->getTranslations('name'))
                ->contains(fn ($name) => is_string($name) && str_contains(mb_strtolower($name), $needle)))
            ->take(4)
            ->values();

        $ids = array_slice($search->textMatches($this->city, $query), 0, 5);

        $businesses = Business::query()->published()->whereKey($ids)->with(['categories', 'neighborhood'])->get()
            ->sortBy(fn (Business $business) => array_search($business->id, $ids, true))
            ->values();

        return ['categories' => $categories, 'businesses' => $businesses];
    }

    public function render(CityDirectory $directory, BusinessSearch $search): View
    {
        $tree = $directory->categoryTree($this->city);
        $location = $this->location();

        return view('livewire.directory.city-home', [
            'groups' => $tree['groups'],
            'counts' => $tree['counts'],
            'pharmacies' => $directory->pharmaciesOnDuty($this->city),
            'recentlyReviewed' => $directory->recentlyReviewed($this->city),
            'openNearby' => $location ? $directory->openNearby($this->city, $location) : null,
            'hasLocation' => $location !== null,
            'suggestions' => $this->suggestions($directory, $search),
        ])
            ->title(__('Services à :city', ['city' => $this->city->name]))
            ->layoutData([
                'metaDescription' => __('Trouvez artisans, mécaniciens, pharmacies de garde et commerces à :city, avec les avis des habitants.', ['city' => $this->city->name]),
                'canonical' => route('city.home', ['city' => $this->city]),
                'structuredData' => [JsonLd::website($this->city, route('city.home', ['city' => $this->city]), route('city.search', ['city' => $this->city]))],
            ]);
    }
}
