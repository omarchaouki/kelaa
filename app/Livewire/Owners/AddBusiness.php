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
 * "Ajouter mon établissement", in steps: activity, contact, address, hours,
 * review. Any member with a verified phone can submit (free); the listing is
 * published only after a moderator validates it.
 */
#[Layout('layouts.app')]
class AddBusiness extends Component
{
    use EditsWeeklyHours;

    public const STEPS = [1 => 'activity', 2 => 'contact', 3 => 'address', 4 => 'hours', 5 => 'review'];

    #[Locked]
    public int $cityId;

    #[Locked]
    public int $step = 1;

    /** Furthest step reached: earlier steps can be revisited from the progress bar. */
    #[Locked]
    public int $maxStep = 1;

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
     * Rules of each step; the last step re-checks everything.
     *
     * @return array<string, mixed>
     */
    protected function stepRules(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => ['required', 'string', 'min:2', 'max:120'],
                'categoryId' => ['required', 'integer', Rule::exists('categories', 'id')->whereNotNull('parent_id')->where('is_active', true)],
                'extraCategoryIds' => ['array', 'max:2'],
                'extraCategoryIds.*' => ['integer', 'different:categoryId', Rule::exists('categories', 'id')->whereNotNull('parent_id')->where('is_active', true)],
            ],
            2 => [
                // A listing nobody can call is useless: at least one number.
                'phone' => ['nullable', 'required_without:whatsapp', 'string', MoroccanPhoneNumber::any()],
                'whatsapp' => ['nullable', 'string', MoroccanPhoneNumber::mobile()],
                'website' => ['nullable', 'string', 'max:255', fn ($attribute, $value, $fail) => $value === '' || $value === null || SafeUrl::isSafe($value) ? null : $fail(__('Adresse web invalide.'))],
            ],
            3 => [
                'neighborhoodId' => ['nullable', 'integer', Rule::exists('neighborhoods', 'id')->where('city_id', $this->cityId)],
                'address' => ['required', 'string', 'max:255'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ],
            4 => [
                'description' => ['nullable', 'string', 'max:2000'],
                'priceRange' => ['nullable', 'integer', 'between:1,4'],
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [...$this->stepRules(1), ...$this->stepRules(2), ...$this->stepRules(3), ...$this->stepRules(4)];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'phone.required_without' => __('Indiquez au moins un téléphone ou un WhatsApp.'),
            'categoryId.required' => __('Choisissez l’activité principale.'),
            'latitude.required' => __('Placez le repère sur la carte.'),
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

    public function toggleExtraCategory(int $categoryId): void
    {
        $this->extraCategoryIds = in_array($categoryId, $this->extraCategoryIds, true)
            ? array_values(array_diff($this->extraCategoryIds, [$categoryId]))
            : array_slice([...$this->extraCategoryIds, $categoryId], 0, 2);
    }

    /**
     * Validates the current step, then moves on (the review step runs the duplicate check).
     */
    public function next(DuplicateDetector $detector): void
    {
        $this->validate($this->stepRules($this->step));

        if ($this->step === 3) {
            $this->assertNearCity();
        }

        if ($this->step === 4 && ! $this->alwaysOpen) {
            $this->validatedWeek();
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        if ($this->step === 4) {
            $this->check($detector);

            return;
        }

        $this->step++;
        $this->maxStep = max($this->maxStep, $this->step);
    }

    public function previous(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->maxStep) {
            $this->step = $step;
        }
    }

    /**
     * Looks for an existing listing before creating a new one, then shows the review.
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
        $this->step = $this->maxStep = 5;
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

        $groups = Category::query()->active()->roots()->ordered()
            ->with(['children' => fn ($q) => $q->where('is_active', true)->ordered()])
            ->get();
        $categories = $groups->flatMap(fn (Category $group) => $group->children)->keyBy('id');

        return view('livewire.owners.add-business', [
            'city' => $city,
            'groups' => $groups,
            'category' => $this->categoryId ? $categories->get($this->categoryId) : null,
            'extraCategories' => $categories->only($this->extraCategoryIds)->values(),
            'neighborhoods' => Neighborhood::query()->where('city_id', $city->id)->where('is_active', true)->orderBy('slug')->get(),
            'neighborhood' => $this->neighborhoodId ? Neighborhood::query()->where('city_id', $city->id)->find($this->neighborhoodId) : null,
            'duplicates' => $duplicates,
        ])->title(__('Ajouter un établissement'))->layoutData(['noindex' => true]);
    }
}
