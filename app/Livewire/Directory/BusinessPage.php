<?php

namespace App\Livewire\Directory;

use App\Enums\ContactEventSource;
use App\Enums\ContactEventType;
use App\Enums\ReplyStatus;
use App\Enums\ReviewStatus;
use App\Models\Business;
use App\Models\City;
use App\Models\Review;
use App\Models\SponsoredPlacement;
use App\Services\Directory\CityDirectory;
use App\Services\Hours\OpeningHours;
use App\Services\Tracking\ContactEventRecorder;
use App\Support\Phone\MoroccanPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class BusinessPage extends Component
{
    use WithPagination;

    public const REVIEWS_PER_PAGE = 10;

    public const REVIEW_SORTS = ['recent', 'helpful', 'highest', 'lowest'];

    #[Locked]
    public City $city;

    #[Locked]
    public Business $business;

    /** Where the visit came from (statistics only). */
    #[Locked]
    public string $source = 'organic';

    #[Locked]
    public ?int $placementId = null;

    #[Url(as: 'avis', except: 'recent')]
    public string $reviewSort = 'recent';

    public function mount(City $city, Business $business, ContactEventRecorder $recorder): void
    {
        abort_unless($city->is_active && $business->isPublished(), 404);

        $this->city = $city;
        $this->business = $business;

        $source = ContactEventSource::tryFrom((string) request()->query('src')) ?? ContactEventSource::Organic;
        $placement = $source === ContactEventSource::Sponsored && is_numeric(request()->query('pl'))
            ? SponsoredPlacement::query()->find((int) request()->query('pl'))
            : null;

        $this->source = $source->value;
        $this->placementId = $placement?->id;

        $recorder->record($business, ContactEventType::ProfileView, $source, request(), $placement);
    }

    public function updatedReviewSort(): void
    {
        $this->resetPage('reviews');
    }

    public function render(CityDirectory $directory): View
    {
        $business = $this->business->load([
            'categories', 'neighborhood', 'hours', 'specialHours', 'city', 'media',
        ]);

        $hours = OpeningHours::for($business);
        $sort = in_array($this->reviewSort, self::REVIEW_SORTS, true) ? $this->reviewSort : 'recent';

        $reviews = $business->reviews()
            ->where('status', ReviewStatus::Published)
            ->with([
                'user:id,name',
                'reply' => fn ($q) => $q->where('status', ReplyStatus::Published),
                'media',
            ])
            ->when($sort === 'recent', fn (Builder $q) => $q->latest('published_at'))
            ->when($sort === 'helpful', fn (Builder $q) => $q->orderByDesc('helpful_count')->latest('published_at'))
            ->when($sort === 'highest', fn (Builder $q) => $q->orderByDesc('rating')->latest('published_at'))
            ->when($sort === 'lowest', fn (Builder $q) => $q->orderBy('rating')->latest('published_at'))
            ->paginate(self::REVIEWS_PER_PAGE, ['*'], 'reviews');

        $distribution = Review::query()
            ->where('business_id', $business->id)
            ->where('status', ReviewStatus::Published)
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->map(fn ($total) => (int) $total)
            ->all();

        $primaryCategory = $business->categories->firstWhere('pivot.is_primary', true) ?? $business->categories->first();

        return view('livewire.directory.business-page', [
            'hours' => $hours,
            'status' => $hours->statusAt(),
            'reviews' => $reviews,
            'distribution' => $distribution,
            'primaryCategory' => $primaryCategory,
            'similar' => $directory->similarNearby($business),
            'whatsappUrl' => $this->whatsappUrl($business),
            'gallery' => $business->getMedia('gallery'),
        ])
            ->title(__(':business, :category à :city', [
                'business' => $business->name,
                'category' => $primaryCategory->name ?? '',
                'city' => $this->city->name,
            ]))
            ->layoutData([
                'metaDescription' => str($business->description ?: __(':business à :city : horaires, avis, téléphone et WhatsApp.', ['business' => $business->name, 'city' => $this->city->name]))->squish()->limit(155)->toString(),
                'canonical' => route('city.business', ['city' => $this->city, 'business' => $business]),
            ]);
    }

    protected function whatsappUrl(Business $business): ?string
    {
        if ($business->whatsapp === null) {
            return null;
        }

        $message = __('Bonjour, je vous contacte via :app au sujet de :business.', [
            'app' => config('app.name'),
            'business' => $business->name,
        ]);

        return 'https://wa.me/'.MoroccanPhone::forWhatsApp($business->whatsapp).'?text='.rawurlencode($message);
    }
}
