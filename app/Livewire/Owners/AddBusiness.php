<?php

namespace App\Livewire\Owners;

use App\Actions\Owners\SubmitBusiness;
use App\Exceptions\RateLimited;
use App\Livewire\Concerns\EditsWeeklyHours;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Rules\MoroccanPhoneNumber;
use App\Services\Owners\DuplicateDetector;
use App\Support\Geo\Coordinates;
use App\Support\Security\SafeUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Any member with a verified phone can suggest a new listing (free).
 * It is published only after moderation.
 */
#[Layout('layouts.app')]
class AddBusiness extends Component
{
    use EditsWeeklyHours;

    #[Locked]
    public int $cityId;

    public string $name = '';

    public ?int $categoryId = null;

    /** @var list<int> */
    public array $extraCategoryIds = [];

    public ?int $neighborhoodId = null;

    public string $address = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public string $phone = '';

    public string $whatsapp = '';

    public string $website = '';

    public string $description = '';

    public ?int $priceRange = null;

    public bool $alwaysOpen = false;

    public bool $confirmedNotDuplicate = false;

    /** @var list<int> */
    #[Locked]
    public array $duplicateIds = [];

    #[Locked]
    public bool $checked = false;

    #[Locked]
    public ?string $submittedName = null;

    public function mount(City $city): void
    {
        abort_unless($city->is_active, 404);

        $user = Auth::user();

        if ($user === null || ! $user->hasVerifiedPhone()) {
            session()->put('url.intended', request()->fullUrl());
            $this->redirectRoute($user === null ? 'login' : 'account.phone', navigate: true);

            return;
        }

        $this->cityId = $city->id;
        $this->defaultWeek();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'categoryId' => ['required', 'integer', Rule::exists('categories', 'id')->whereNotNull('parent_id')->where('is_active', true)],
            'extraCategoryIds' => ['array', 'max:2'],
            'extraCategoryIds.*' => ['integer', Rule::exists('categories', 'id')->whereNotNull('parent_id')->where('is_active', true)],
            'neighborhoodId' => ['nullable', 'integer', Rule::exists('neighborhoods', 'id')->where('city_id', $this->cityId)],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', MoroccanPhoneNumber::any()],
            'whatsapp' => ['nullable', 'string', MoroccanPhoneNumber::mobile()],
            'website' => ['nullable', 'string', 'max:255', fn ($attribute, $value, $fail) => $value === '' || $value === null || SafeUrl::isSafe($value) ? null : $fail(__('Adresse web invalide.'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'priceRange' => ['nullable', 'integer', 'between:1,4'],
        ];
    }

    /**
     * Changing what identifies the business invalidates the duplicate check.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['name', 'phone', 'whatsapp', 'latitude', 'longitude'], true)) {
            $this->checked = false;
            $this->duplicateIds = [];
            $this->confirmedNotDuplicate = false;
        }
    }

    /**
     * Step 1: look for an existing listing before creating a new one.
     */
    public function check(DuplicateDetector $detector): void
    {
        $this->validate();
        $this->assertNearCity();

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->duplicateIds = $detector
            ->find($this->cityId, $this->name, new Coordinates((float) $this->latitude, (float) $this->longitude), $this->phone ?: null, $this->whatsapp ?: null)
            ->pluck('id')
            ->all();

        $this->checked = true;
        $this->confirmedNotDuplicate = $this->duplicateIds === [];
    }

    public function submit(SubmitBusiness $submit): void
    {
        if (! $this->checked) {
            return;
        }

        $this->validate();
        $this->assertNearCity();
        $week = $this->alwaysOpen ? [] : $this->validatedWeek();

        if ($this->duplicateIds !== [] && ! $this->confirmedNotDuplicate) {
            $this->addError('confirmedNotDuplicate', __('Confirmez qu’il ne s’agit pas d’un établissement déjà présent.'));
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        try {
            $business = $submit(Auth::user(), [
                'city_id' => $this->cityId,
                'neighborhood_id' => $this->neighborhoodId,
                'name' => $this->name,
                'description' => $this->description ?: null,
                'address' => $this->address,
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
                'phone' => $this->phone ?: null,
                'whatsapp' => $this->whatsapp ?: null,
                'website' => $this->website ?: null,
                'price_range' => $this->priceRange,
                'is_always_open' => $this->alwaysOpen,
            ], [(int) $this->categoryId, ...array_map('intval', $this->extraCategoryIds)], $week);
        } catch (RateLimited $e) {
            $this->addError('name', __('Trop de tentatives. Réessayez dans :minutes min.', ['minutes' => $e->minutes()]));

            return;
        }

        $this->submittedName = $business->name;
    }

    /**
     * A listing must be in (or very near) the city it is filed under.
     */
    private function assertNearCity(): void
    {
        $city = City::query()->findOrFail($this->cityId);
        $center = new Coordinates($city->center_latitude, $city->center_longitude);
        $point = Coordinates::tryFrom($this->latitude, $this->longitude);

        if ($point === null || $center->distanceTo($point) > 50_000) {
            $this->addError('latitude', __('Placez le repère dans la ville.'));
        }
    }

    public function render(): View
    {
        $city = City::query()->findOrFail($this->cityId);

        /** @var Collection<int, Business> $duplicates */
        $duplicates = $this->duplicateIds === []
            ? new Collection
            : Business::query()->whereKey($this->duplicateIds)->with('categories')->get();

        return view('livewire.owners.add-business', [
            'city' => $city,
            'categories' => Category::query()->active()->whereNotNull('parent_id')->with('parent')->ordered()->get()
                ->sortBy(fn (Category $c) => $c->parent?->name.' '.$c->name),
            'neighborhoods' => Neighborhood::query()->where('city_id', $city->id)->where('is_active', true)->orderBy('slug')->get(),
            'duplicates' => $duplicates,
        ])->title(__('Ajouter un établissement'));
    }
}
