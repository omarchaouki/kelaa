<?php

namespace App\Livewire\Directory;

use App\Livewire\Concerns\WithVisitorLocation;
use App\Models\Category;
use App\Models\City;
use App\Services\Directory\CityDirectory;
use App\Support\Seo\JsonLd;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * City home: every category with its number of listings, filtered instantly
 * in the browser, plus tonight's pharmacies and recent activity. Business
 * search lives in the nested QuickSearch component.
 */
#[Layout('layouts.app')]
class CityHome extends Component
{
    use WithVisitorLocation;

    /** Popular categories shown first (the most listings). */
    public const POPULAR = 8;

    #[Locked]
    public City $city;

    public function mount(City $city): void
    {
        abort_unless($city->is_active, 404);

        $this->city = $city;
    }

    public function render(CityDirectory $directory): View
    {
        $tree = $directory->categoryTree($this->city);
        $counts = $tree['counts'];
        $location = $this->location();

        $popular = $tree['groups']
            ->flatMap(fn (Category $group) => $group->children)
            ->filter(fn (Category $category) => ($counts[$category->id] ?? 0) > 0)
            ->sortByDesc(fn (Category $category) => $counts[$category->id])
            ->take(self::POPULAR)
            ->values();

        return view('livewire.directory.city-home', [
            'groups' => $tree['groups'],
            'counts' => $counts,
            'popular' => $popular,
            'index' => $directory->categorySearchIndex($tree['groups']),
            'stats' => $directory->stats($this->city) + [
                'categories' => $tree['groups']->sum(fn (Category $group) => $group->children->count()),
            ],
            'pharmacies' => $directory->pharmaciesOnDuty($this->city),
            'recentlyReviewed' => $directory->recentlyReviewed($this->city),
            'openNearby' => $location ? $directory->openNearby($this->city, $location) : null,
            'hasLocation' => $location !== null,
        ])
            ->title(__('Services à :city', ['city' => $this->city->name]))
            ->layoutData([
                'wide' => true,
                'metaDescription' => __('Trouvez artisans, mécaniciens, pharmacies de garde et commerces à :city, avec les avis des habitants.', ['city' => $this->city->name]),
                'canonical' => route('city.home', ['city' => $this->city]),
                'structuredData' => [JsonLd::website($this->city, route('city.home', ['city' => $this->city]), route('city.search', ['city' => $this->city]))],
            ]);
    }
}
