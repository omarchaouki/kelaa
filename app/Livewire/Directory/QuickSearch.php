<?php

namespace App\Livewire\Directory;

use App\Models\Business;
use App\Models\City;
use App\Services\Search\BusinessSearch;
use App\Services\Search\SearchFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Search box of the city home. Kept in its own small component so typing
 * only re-renders the suggestions, never the category grid: the grid is
 * filtered instantly in the browser from the "home-filter" event.
 */
class QuickSearch extends Component
{
    #[Locked]
    public int $cityId;

    public string $q = '';

    public function mount(City $city): void
    {
        $this->cityId = $city->id;
    }

    public function search(): mixed
    {
        return $this->redirectRoute('city.search', ['city' => $this->city(), 'q' => SearchFilters::cleanQuery($this->q) ?: null], navigate: true);
    }

    private function city(): City
    {
        return City::query()->findOrFail($this->cityId);
    }

    /**
     * @return Collection<int, Business>
     */
    private function businesses(BusinessSearch $search): Collection
    {
        $query = SearchFilters::cleanQuery($this->q);

        if (mb_strlen($query) < 2) {
            return collect();
        }

        $ids = array_slice($search->textMatches($this->city(), $query), 0, 5);

        return Business::query()->published()->whereKey($ids)->with(['categories', 'neighborhood'])->get()
            ->sortBy(fn (Business $business) => array_search($business->id, $ids, true))
            ->values();
    }

    public function render(BusinessSearch $search): View
    {
        return view('livewire.directory.quick-search', [
            'city' => $this->city(),
            'businesses' => $this->businesses($search),
        ]);
    }
}
