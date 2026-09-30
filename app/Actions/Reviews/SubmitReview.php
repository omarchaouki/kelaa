<?php

namespace App\Actions\Reviews;

use App\Enums\ReviewStatus;
use App\Exceptions\ReviewNotAllowed;
use App\Jobs\RecalculateBusinessRating;
use App\Models\Business;
use App\Models\Review;
use App\Models\ReviewRevision;
use App\Models\User;
use App\Notifications\NewReviewOnYourBusiness;
use App\Services\Reviews\ReviewAbuseDetector;
use App\Services\Reviews\ReviewEligibility;
use App\Support\Moderation\ModerationGate;
use App\Support\Security\SafeImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Creates a review, or edits the author's existing one (one review per
 * user and business; the previous version is kept as a revision).
 *
 * The abuse rules decide between "published" and "pending": the author
 * never chooses the status, and editing cannot lift a moderation decision.
 */
class SubmitReview
{
    public function __construct(
        private readonly ReviewEligibility $eligibility,
        private readonly ReviewAbuseDetector $detector,
    ) {}

    /**
     * @param  list<UploadedFile>  $photos  New photos to attach.
     * @param  list<int>  $removePhotoIds  Photos of this review to delete.
     *
     * @throws ReviewNotAllowed
     */
    public function __invoke(
        User $author,
        Business $business,
        int $rating,
        ?string $body,
        string $ipHash,
        string $deviceHash,
        array $photos = [],
        array $removePhotoIds = [],
    ): Review {
        if ($denial = $this->eligibility->denialFor($author, $business)) {
            throw new ReviewNotAllowed($denial);
        }

        $body = self::cleanBody($body);

        $review = DB::transaction(function () use ($author, $business, $rating, $body, $ipHash, $deviceHash): Review {
            $existing = Review::query()
                ->where('business_id', $business->id)
                ->where('user_id', $author->id)
                ->lockForUpdate()
                ->first();

            $flags = $this->detector->flags($author, $business, $body, $ipHash, $deviceHash, $existing);

            return $existing === null
                ? $this->create($author, $business, $rating, $body, $ipHash, $deviceHash, $flags)
                : $this->edit($existing, $rating, $body, $ipHash, $deviceHash, $flags);
        });

        $this->syncPhotos($review, $photos, $removePhotoIds);

        RecalculateBusinessRating::dispatch($business->id)->afterCommit();

        if ($review->wasRecentlyCreated && $review->isPublished()) {
            $business->owner?->notify(new NewReviewOnYourBusiness($review));
        }

        return $review;
    }

    /**
     * @param  list<string>  $flags
     */
    private function create(User $author, Business $business, int $rating, ?string $body, string $ipHash, string $deviceHash, array $flags): Review
    {
        $review = new Review([
            'business_id' => $business->id,
            'user_id' => $author->id,
            'rating' => $rating,
            'body' => $body,
            'locale' => app()->getLocale(),
        ]);

        // New rows may take their initial status; only updates are gated.
        $review->forceFill([
            'status' => $flags === [] ? ReviewStatus::Published : ReviewStatus::Pending,
            'flags' => $flags === [] ? null : $flags,
            'published_at' => $flags === [] ? now() : null,
            'ip_hash' => $ipHash,
            'device_hash' => $deviceHash,
        ])->save();

        return $review;
    }

    /**
     * @param  list<string>  $flags
     */
    private function edit(Review $review, int $rating, ?string $body, string $ipHash, string $deviceHash, array $flags): Review
    {
        if ($review->rating === $rating && $review->body === $body) {
            return $review;
        }

        ReviewRevision::query()->create([
            'review_id' => $review->id,
            'rating' => $review->rating,
            'body' => $review->body,
            'created_at' => now(),
        ]);

        $review->fill(['rating' => $rating, 'body' => $body]);
        $review->forceFill(['edited_at' => now(), 'ip_hash' => $ipHash, 'device_hash' => $deviceHash]);

        $status = match (true) {
            // Only a moderator can bring a hidden review back.
            $review->status === ReviewStatus::HiddenByModeration => ReviewStatus::HiddenByModeration,
            // A flagged edit, or an edit of a rejected review, is checked again.
            $flags !== [], $review->status === ReviewStatus::Rejected => ReviewStatus::Pending,
            default => $review->status,
        };

        if ($status !== $review->status || $flags !== ($review->flags ?? [])) {
            ModerationGate::asSystem(function () use ($review, $status, $flags): void {
                $review->forceFill(['status' => $status, 'flags' => $flags === [] ? null : $flags]);

                if ($status === ReviewStatus::Pending) {
                    $review->forceFill(['moderated_by' => null, 'moderated_at' => null, 'moderation_reason' => null]);
                }

                $review->save();
            });

            return $review;
        }

        $review->save();

        return $review;
    }

    /**
     * @param  list<UploadedFile>  $photos
     * @param  list<int>  $removeIds
     */
    private function syncPhotos(Review $review, array $photos, array $removeIds): void
    {
        if ($removeIds !== []) {
            // Scoped to this review: ids of other people's media are ignored.
            $review->media()->where('collection_name', 'photos')->whereKey($removeIds)->get()->each->delete();
        }

        $room = (int) config('reviews.max_photos') - $review->media()->where('collection_name', 'photos')->count();

        foreach (array_slice($photos, 0, max(0, $room)) as $photo) {
            $review->addMedia(SafeImage::reencode($photo))
                ->usingFileName(bin2hex(random_bytes(12)).'.jpg')
                ->toMediaCollection('photos');
        }
    }

    public static function cleanBody(?string $body): ?string
    {
        if ($body === null) {
            return null;
        }

        // Control characters (except newlines) and invisible direction overrides are stripped.
        $body = preg_replace('/[^\P{C}\n]+/u', '', $body) ?? '';
        $body = preg_replace("/\n{3,}/", "\n\n", str_replace("\r\n", "\n", $body)) ?? '';
        $body = trim($body);

        return $body === '' ? null : $body;
    }
}
