<?php

namespace App\Livewire\Directory;

use App\Actions\Reports\SubmitReport;
use App\Actions\Reviews\ReplyToReview;
use App\Actions\Reviews\ToggleHelpfulVote;
use App\Enums\ContactEventSource;
use App\Enums\ContactEventType;
use App\Enums\ReplyStatus;
use App\Enums\ReportReason;
use App\Enums\ReviewStatus;
use App\Exceptions\TooManyReports;
use App\Models\Business;
use App\Models\City;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\ReviewVote;
use App\Models\SponsoredPlacement;
use App\Services\Directory\CityDirectory;
use App\Services\Hours\OpeningHours;
use App\Services\Plans\PlanFeatures;
use App\Services\Tracking\ContactEventRecorder;
use App\Support\Phone\MoroccanPhone;
use App\Support\Seo\JsonLd;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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

    /** Report dialog state. The target is re-validated server-side on submit. */
    public ?string $reportType = null;

    public ?int $reportId = null;

    public string $reportReason = '';

    public string $reportDetails = '';

    public ?string $reportFlash = null;

    /** @var array<int, string> Owner reply drafts keyed by review id. */
    public array $replyBodies = [];

    public function updatedReviewSort(): void
    {
        $this->resetPage('reviews');
    }

    #[On('review-saved')]
    public function refreshReviews(): void
    {
        $this->business->refresh();
    }

    public function openReport(string $type, int $id): mixed
    {
        if (! Auth::check()) {
            return $this->redirectToLogin();
        }

        if ($this->reportTarget($type, $id) === null) {
            return null;
        }

        $this->reportType = $type;
        $this->reportId = $id;
        $this->reportFlash = null;
        $this->reset('reportReason', 'reportDetails');
        $this->resetErrorBag();

        return null;
    }

    public function closeReport(): void
    {
        $this->reset('reportType', 'reportId', 'reportReason', 'reportDetails');
    }

    public function submitReport(SubmitReport $submit): mixed
    {
        $user = Auth::user();

        if ($user === null) {
            return $this->redirectToLogin();
        }

        $this->validate([
            'reportReason' => ['required', Rule::enum(ReportReason::class)],
            'reportDetails' => ['nullable', 'string', 'max:'.SubmitReport::MAX_DETAILS],
        ]);

        $target = $this->reportTarget((string) $this->reportType, (int) $this->reportId);

        if ($target === null) {
            $this->closeReport();

            return null;
        }

        try {
            $submit($user, $target, ReportReason::from($this->reportReason), $this->reportDetails);
        } catch (TooManyReports) {
            $this->addError('reportReason', __('Vous avez envoyé beaucoup de signalements. Réessayez plus tard.'));

            return null;
        }

        $this->closeReport();
        $this->reportFlash = __('Merci, notre équipe va examiner ce signalement.');

        return null;
    }

    public function toggleHelpful(int $reviewId, ToggleHelpfulVote $toggle): mixed
    {
        $user = Auth::user();

        if ($user === null) {
            return $this->redirectToLogin();
        }

        $review = $this->ownedReview($reviewId);

        if ($review !== null && $user->can('vote', $review)) {
            $toggle($user, $review);
        }

        return null;
    }

    public function saveReply(int $reviewId, ReplyToReview $reply): void
    {
        $user = Auth::user();
        $review = $this->ownedReview($reviewId);

        if ($user === null || $review === null || ! $user->can('reply', $review)) {
            return;
        }

        $this->validate([
            "replyBodies.{$reviewId}" => ['required', 'string', 'min:'.ReplyToReview::MIN_LENGTH, 'max:'.ReplyToReview::MAX_LENGTH],
        ], attributes: ["replyBodies.{$reviewId}" => __('réponse')]);

        $reply($user, $review, $this->replyBodies[$reviewId]);
        unset($this->replyBodies[$reviewId]);
    }

    /**
     * Only content of THIS business can be targeted, whatever the browser sends.
     */
    private function reportTarget(string $type, int $id): ?Model
    {
        return match ($type) {
            'business' => $id === $this->business->id ? $this->business : null,
            'review' => $this->ownedReview($id),
            'review_reply' => ReviewReply::query()->whereKey($id)
                ->whereHas('review', fn (Builder $q) => $q->where('business_id', $this->business->id)->where('status', ReviewStatus::Published))
                ->first(),
            'media' => Media::query()->whereKey($id)
                ->where(fn (Builder $q) => $q
                    ->where(fn (Builder $q) => $q->where('model_type', 'business')->where('model_id', $this->business->id))
                    ->orWhere(fn (Builder $q) => $q->where('model_type', 'review')
                        ->whereIn('model_id', Review::query()->select('id')->where('business_id', $this->business->id)->where('status', ReviewStatus::Published))))
                ->whereIn('collection_name', SubmitReport::PUBLIC_MEDIA_COLLECTIONS)
                ->first(),
            default => null,
        };
    }

    private function ownedReview(int $id): ?Review
    {
        return Review::query()
            ->whereKey($id)
            ->where('business_id', $this->business->id)
            ->where('status', ReviewStatus::Published)
            ->with('business')
            ->first();
    }

    private function redirectToLogin(): mixed
    {
        session()->put('url.intended', route('city.business', ['city' => $this->city, 'business' => $this->business]));

        return $this->redirectRoute('login', navigate: true);
    }

    public function render(CityDirectory $directory): View
    {
        $business = $this->business->load([
            'categories.parent', 'neighborhood', 'hours', 'specialHours', 'city', 'media',
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
        $url = route('city.business', ['city' => $this->city, 'business' => $business]);
        $breadcrumbs = [['name' => $this->city->name, 'url' => route('city.home', ['city' => $this->city])]];

        if ($primaryCategory !== null) {
            $breadcrumbs[] = ['name' => $primaryCategory->name, 'url' => route('city.category', ['city' => $this->city, 'category' => $primaryCategory])];
        }

        $breadcrumbs[] = ['name' => $business->name, 'url' => $url];

        return view('livewire.directory.business-page', [
            'hours' => $hours,
            'status' => $hours->statusAt(),
            'reviews' => $reviews,
            'votedIds' => Auth::id() === null ? [] : ReviewVote::query()
                ->where('user_id', Auth::id())
                ->whereIn('review_id', collect($reviews->items())->pluck('id'))
                ->pluck('review_id')
                ->all(),
            'isOwner' => Auth::id() !== null && $business->owner_id === Auth::id(),
            'reportReasons' => ReportReason::cases(),
            'distribution' => $distribution,
            'primaryCategory' => $primaryCategory,
            'similar' => $directory->similarNearby($business),
            'whatsappUrl' => $this->whatsappUrl($business),
            // Photos beyond the current plan's limit are kept but not shown (graceful downgrade).
            'gallery' => $business->getMedia('gallery')->take(app(PlanFeatures::class)->maxPhotos($business)),
        ])
            ->title(__(':business, :category à :city', [
                'business' => $business->name,
                'category' => $primaryCategory->name ?? '',
                'city' => $this->city->name,
            ]))
            ->layoutData([
                'wide' => true,
                'metaDescription' => str($business->description ?: __(':business à :city : horaires, avis, téléphone et WhatsApp.', ['business' => $business->name, 'city' => $this->city->name]))->squish()->limit(155)->toString(),
                'canonical' => $url,
                'ogType' => 'business.business',
                'structuredData' => [JsonLd::localBusiness($business, $primaryCategory, $url), JsonLd::breadcrumbs($breadcrumbs)],
                'ogImage' => $business->getFirstMediaUrl('cover', 'large') ?: ($business->getFirstMedia('gallery')?->getUrl('large') ?: null),
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
