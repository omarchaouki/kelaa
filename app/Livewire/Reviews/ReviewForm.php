<?php

namespace App\Livewire\Reviews;

use App\Actions\Reviews\SubmitReview;
use App\Enums\ReviewStatus;
use App\Exceptions\ReviewNotAllowed;
use App\Models\Business;
use App\Models\City;
use App\Models\Review;
use App\Services\Reviews\ReviewEligibility;
use App\Support\Security\Fingerprint;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Write or edit one's review of a business (one review per user and business).
 */
class ReviewForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    #[Locked]
    public int $cityId;

    public int $rating = 0;

    public string $body = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $photos = [];

    /** @var list<int> */
    public array $removePhotoIds = [];

    public bool $editing = false;

    public ?string $flash = null;

    public function mount(Business $business): void
    {
        $this->businessId = $business->id;
        $this->cityId = $business->city_id;

        if ($review = $this->ownReview()) {
            $this->rating = $review->rating;
            $this->body = (string) $review->body;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $min = (int) config('reviews.min_body_length');
        $max = (int) config('reviews.max_body_length');

        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['nullable', 'string', 'max:'.$max, function (string $attribute, mixed $value, \Closure $fail) use ($min): void {
                $clean = SubmitReview::cleanBody(is_string($value) ? $value : null);

                if ($clean !== null && mb_strlen($clean) < $min) {
                    $fail(__('Votre avis doit contenir au moins :min caractères.', ['min' => $min]));
                }
            }],
            'photos' => ['array', 'max:'.config('reviews.max_photos')],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('reviews.max_photo_kb'), 'dimensions:max_width=12000,max_height=12000'],
            'removePhotoIds' => ['array'],
            'removePhotoIds.*' => ['integer'],
        ];
    }

    public function startEditing(): void
    {
        $this->editing = true;
    }

    public function removeUpload(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function toggleRemovePhoto(int $mediaId): void
    {
        $this->removePhotoIds = in_array($mediaId, $this->removePhotoIds, true)
            ? array_values(array_diff($this->removePhotoIds, [$mediaId]))
            : [...$this->removePhotoIds, $mediaId];
    }

    public function save(SubmitReview $submit): mixed
    {
        $user = Auth::user();
        $business = Business::query()->findOrFail($this->businessId);

        if ($user === null) {
            return $this->redirectToLogin($business);
        }

        $key = 'review-submit:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('body', __('Trop de tentatives. Réessayez dans :minutes min.', ['minutes' => 1]));

            return null;
        }

        RateLimiter::hit($key, 60);

        $this->validate();

        try {
            $review = $submit(
                $user,
                $business,
                $this->rating,
                $this->body,
                Fingerprint::ip(request()),
                Fingerprint::device(request()),
                array_values($this->photos),
                $this->removePhotoIds,
            );
        } catch (ReviewNotAllowed $e) {
            $this->addError('rating', $e->getMessage());

            return null;
        } catch (InvalidArgumentException) {
            $this->addError('photos', __('Une des photos n’a pas pu être lue.'));

            return null;
        }

        $this->reset('photos', 'removePhotoIds', 'editing');
        $this->flash = $review->status === ReviewStatus::Published
            ? __('Merci ! Votre avis est publié.')
            : __('Merci ! Votre avis sera publié après vérification par notre équipe.');

        $this->dispatch('review-saved');

        return null;
    }

    private function redirectToLogin(Business $business): mixed
    {
        $city = City::query()->findOrFail($this->cityId);
        session()->put('url.intended', route('city.business', ['city' => $city, 'business' => $business]).'#avis');

        return $this->redirectRoute('login', navigate: true);
    }

    private function ownReview(): ?Review
    {
        $userId = Auth::id();

        return $userId === null ? null : Review::query()
            ->where('business_id', $this->businessId)
            ->where('user_id', $userId)
            ->with('media')
            ->first();
    }

    public function render(ReviewEligibility $eligibility): View
    {
        $business = Business::query()->with('city')->findOrFail($this->businessId);

        return view('livewire.reviews.review-form', [
            'business' => $business,
            'existing' => $this->ownReview(),
            'denial' => $eligibility->denialFor(Auth::user(), $business),
            'minLength' => (int) config('reviews.min_body_length'),
            'maxLength' => (int) config('reviews.max_body_length'),
        ]);
    }
}
