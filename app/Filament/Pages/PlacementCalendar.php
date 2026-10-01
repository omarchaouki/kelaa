<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\SponsoredPlacement;
use App\Services\Billing\PlacementInventory;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

/**
 * Availability calendar of sponsored slots: weeks x positions for one
 * city / category / zone, with the business holding each slot.
 */
class PlacementCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 43;

    protected string $view = 'filament.pages.placement-calendar';

    #[Url]
    public ?int $cityId = null;

    #[Url]
    public ?int $categoryId = null;

    #[Url]
    public ?int $neighborhoodId = null;

    public static function getNavigationGroup(): ?string
    {
        return __('Monétisation');
    }

    public static function getNavigationLabel(): string
    {
        return __('Calendrier des emplacements');
    }

    public function getTitle(): string
    {
        return __('Calendrier des emplacements');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && ! $user->isBanned() && $user->can(Permission::ManagePlacements->value);
    }

    public function mount(): void
    {
        $this->cityId ??= City::query()->orderBy('id')->value('id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $rows = [];

        if ($this->cityId !== null && $this->categoryId !== null) {
            $monday = now()->toImmutable()->startOfWeek(CarbonInterface::MONDAY);
            $inventory = app(PlacementInventory::class);

            for ($i = 0; $i < 16; $i++) {
                $start = $monday->addWeeks($i);
                $cells = [];

                foreach ([1, 2, 3] as $position) {
                    /** @var SponsoredPlacement|null $holder */
                    $holder = $inventory->occupying((int) $this->cityId, (int) $this->categoryId, $this->neighborhoodId, $position, $start, $start->addWeek())
                        ->with('business')
                        ->first();

                    $cells[$position] = $holder === null ? null : [
                        'business' => $holder->business->name,
                        'status' => $holder->status->getLabel(),
                    ];
                }

                $rows[] = ['week' => $start, 'cells' => $cells];
            }
        }

        return [
            'cities' => City::query()->orderBy('slug')->get(),
            'categories' => Category::query()->whereNotNull('parent_id')->orderBy('slug')->get(),
            'neighborhoods' => $this->cityId ? Neighborhood::query()->where('city_id', $this->cityId)->orderBy('slug')->get() : collect(),
            'rows' => $rows,
        ];
    }
}
