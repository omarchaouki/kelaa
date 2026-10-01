<?php

namespace App\Livewire\Owners;

use App\Actions\Owners\UpdateBusinessProfile;
use App\Actions\Reviews\ReplyToReview;
use App\Enums\ReviewStatus;
use App\Livewire\Concerns\EditsWeeklyHours;
use App\Models\Business;
use App\Services\Owners\OwnerStatistics;
use App\Services\Owners\ProfileCompleteness;
use App\Services\Plans\PlanFeatures;
use App\Support\Security\SafeUrl;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Manage one listing. Every request re-checks that the current user owns it
 * (the business id comes from the URL and is never trusted by itself).
 */
#[Layout('layouts.app')]
class BusinessDashboard extends Component
{
    use EditsWeeklyHours, WithFileUploads, WithPagination;

    public const TABS = ['overview', 'info', 'hours', 'photos', 'reviews', 'stats'];

    #[Locked]
    public int $businessId;

    #[Url(except: 'overview')]
    public string $tab = 'overview';

    #[Url(as: 'periode', except: '30d')]
    public string $period = '30d';

    /** @var array<string, string> */
    public array $description = ['fr' => '', 'ar' => '', 'en' => ''];

    public string $email = '';

    public string $website = '';

    /** @var array<string, string> */
    public array $social = [];

    public ?int $priceRange = null;

    public bool $alwaysOpen = false;

    /** @var array{label: string, starts_on: string, ends_on: string, opens_at: string, closes_at: string, is_closed: bool} */
    public array $special = ['label' => '', 'starts_on' => '', 'ends_on' => '', 'opens_at' => '10:00', 'closes_at' => '16:00', 'is_closed' => false];

    /** @var array<int, TemporaryUploadedFile> */
    public array $photos = [];

    public ?TemporaryUploadedFile $logo = null;

    /** @var array<int, string> */
    public array $replyBodies = [];

    public ?string $flash = null;

    public function mount(Business $business): void
    {
        Gate::authorize('manage', $business);

        $this->businessId = $business->id;
        $this->tab = in_array($this->tab, self::TABS, true) ? $this->tab : 'overview';

        foreach (['fr', 'ar', 'en'] as $locale) {
            $this->description[$locale] = (string) $business->getTranslation('description', $locale, false);
        }

        $this->email = (string) $business->email;
        $this->website = (string) $business->website;
        $this->social = array_merge(array_fill_keys(Business::SOCIAL_NETWORKS, ''), array_map('strval', (array) $business->social_links));
        $this->priceRange = $business->price_range;
        $this->alwaysOpen = $business->is_always_open;
        $this->weekFrom($business);
    }

    /**
     * Re-authorizes on every request.
     */
    protected function business(): Business
    {
        $business = Business::query()->findOrFail($this->businessId);
        Gate::authorize('manage', $business);

        return $business;
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'overview';
        $this->flash = null;
    }

    public function saveInfo(UpdateBusinessProfile $update): void
    {
        $this->validate([
            'description.*' => ['nullable', 'string', 'max:2000'],
            'email' => ['nullable', 'email', 'max:190'],
            'website' => ['nullable', 'string', 'max:255', fn ($a, $v, $fail) => $v === '' || $v === null || SafeUrl::isSafe($v) ? null : $fail(__('Adresse web invalide.'))],
            'social.*' => ['nullable', 'string', 'max:255', fn ($a, $v, $fail) => $v === '' || $v === null || SafeUrl::isSafe($v) ? null : $fail(__('Adresse web invalide.'))],
            'priceRange' => ['nullable', 'integer', 'between:1,4'],
        ]);

        $update->details(Auth::user(), $this->business(), [
            'description' => array_map(fn ($text) => $text === '' ? null : $text, array_intersect_key($this->description, array_flip(['fr', 'ar', 'en']))),
            'email' => $this->email ?: null,
            'website' => $this->website ?: null,
            'social_links' => array_intersect_key($this->social, array_flip(Business::SOCIAL_NETWORKS)),
            'price_range' => $this->priceRange,
        ]);

        $this->flash = __('Modifications enregistrées.');
    }

    public function saveHours(UpdateBusinessProfile $update): void
    {
        $week = $this->alwaysOpen ? [] : $this->validatedWeek();

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $business = $this->business();
        $update->details(Auth::user(), $business, ['is_always_open' => $this->alwaysOpen]);
        $update->hours(Auth::user(), $business, $week);

        $this->flash = __('Horaires enregistrés.');
    }

    public function addSpecialPeriod(UpdateBusinessProfile $update): void
    {
        $this->validate([
            'special.label' => ['nullable', 'string', 'max:80'],
            'special.starts_on' => ['required', 'date', 'after_or_equal:today'],
            'special.ends_on' => ['required', 'date', 'after_or_equal:special.starts_on', 'before:'.now()->addYear()->toDateString()],
            'special.is_closed' => ['boolean'],
            'special.opens_at' => ['exclude_if:special.is_closed,true', 'required', 'date_format:H:i'],
            'special.closes_at' => ['exclude_if:special.is_closed,true', 'required', 'date_format:H:i'],
        ]);

        $update->addSpecialPeriod(Auth::user(), $this->business(), [
            'label' => $this->special['label'] ?: null,
            'starts_on' => $this->special['starts_on'],
            'ends_on' => $this->special['ends_on'],
            'opens_at' => $this->special['opens_at'] ?: null,
            'closes_at' => $this->special['closes_at'] ?: null,
            'is_closed' => (bool) $this->special['is_closed'],
        ]);

        $this->reset('special');
        $this->flash = __('Période ajoutée.');
    }

    public function removeSpecialPeriod(int $periodId, UpdateBusinessProfile $update): void
    {
        $update->removeSpecialPeriod(Auth::user(), $this->business(), $periodId);
    }

    public function uploadPhotos(UpdateBusinessProfile $update, PlanFeatures $plans): void
    {
        $business = $this->business();
        $max = $plans->maxPhotos($business);

        $this->validate([
            'photos' => ['required', 'array', 'max:'.max(1, $max)],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=12000,max_height=12000'],
        ]);

        try {
            $added = $update->addPhotos(Auth::user(), $business, array_values($this->photos));
        } catch (InvalidArgumentException) {
            $this->addError('photos', __('Une des photos n’a pas pu être lue.'));

            return;
        }

        $this->reset('photos');
        $this->flash = $added > 0
            ? __(':count photo(s) ajoutée(s).', ['count' => $added])
            : __('Limite de photos atteinte pour votre formule.');
    }

    public function uploadLogo(UpdateBusinessProfile $update): void
    {
        $this->validate(['logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);

        try {
            $update->addPhotos(Auth::user(), $this->business(), [$this->logo], 'logo');
        } catch (AuthorizationException $e) {
            $this->addError('logo', $e->getMessage());

            return;
        }

        $this->reset('logo');
        $this->flash = __('Logo enregistré.');
    }

    public function removePhoto(int $mediaId, UpdateBusinessProfile $update): void
    {
        $update->removePhoto(Auth::user(), $this->business(), $mediaId);
    }

    public function saveReply(int $reviewId, ReplyToReview $reply): void
    {
        $business = $this->business();
        $review = $business->reviews()->whereKey($reviewId)->where('status', ReviewStatus::Published)->first();

        if ($review === null) {
            return;
        }

        $this->validate([
            "replyBodies.{$reviewId}" => ['required', 'string', 'min:'.ReplyToReview::MIN_LENGTH, 'max:'.ReplyToReview::MAX_LENGTH],
        ], attributes: ["replyBodies.{$reviewId}" => __('réponse')]);

        $reply(Auth::user(), $review, $this->replyBodies[$reviewId]);
        unset($this->replyBodies[$reviewId]);
        $this->flash = __('Réponse publiée.');
    }

    public function render(PlanFeatures $plans, ProfileCompleteness $completeness, OwnerStatistics $statistics): View
    {
        $business = $this->business()->load(['city', 'categories', 'hours', 'specialHours', 'media', 'plan']);
        $canSeeStats = Gate::allows('viewStats', $business);

        return view('livewire.owners.business-dashboard', [
            'business' => $business,
            'plan' => $plans->activePlan($business),
            'maxPhotos' => $plans->maxPhotos($business),
            'logoAllowed' => $plans->enabled($business, 'logo_and_cover'),
            'checklist' => $completeness->checklist($business),
            'canSeeStats' => $canSeeStats,
            'stats' => $canSeeStats && $this->tab === 'stats' ? $statistics->summary($business, $this->period) : null,
            'reviews' => $this->tab === 'reviews'
                ? $business->reviews()->where('status', ReviewStatus::Published)->with(['user:id,name', 'reply'])->latest('published_at')->paginate(10, ['*'], 'avis')
                : null,
        ])->title(__('Gérer :business', ['business' => $business->name]))->layoutData(['noindex' => true]);
    }
}
