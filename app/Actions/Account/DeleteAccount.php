<?php

namespace App\Actions\Account;

use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\OtpCode;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use App\Services\Ranking\RankingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Deletes an account (law 09-08 right to erasure). By default the reviews
 * stay, signed "Utilisateur supprimé" (the user row is gone, so they are no
 * longer linked to anyone); on request they are removed as well.
 *
 * Kept on purpose: invoices and payments (accounting obligations, they belong
 * to the business) and the moderation audit trail.
 */
class DeleteAccount
{
    public function __construct(
        private readonly RankingService $ranking,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function __invoke(User $user, bool $removeReviews = false): void
    {
        // Staff accounts are closed by another admin, never self-deleted.
        if ($user->isStaff()) {
            throw new AuthorizationException('Staff accounts are closed by an administrator.');
        }

        // Callers must log the user out first (logout() saves the model).
        $affectedBusinessIds = [];

        DB::transaction(function () use ($user, $removeReviews, &$affectedBusinessIds): void {
            if ($removeReviews) {
                Review::query()->where('user_id', $user->id)->with('media')->each(function (Review $review) use (&$affectedBusinessIds): void {
                    $affectedBusinessIds[] = $review->business_id;
                    $review->clearMediaCollection('photos');
                    $review->delete();
                });

                ReviewReply::query()->where('user_id', $user->id)->each(fn (ReviewReply $reply) => $reply->delete());
            }

            // Claim documents (ID, trade register) are private files: deleted with the claim.
            BusinessClaim::query()->where('user_id', $user->id)->each(function (BusinessClaim $claim): void {
                $claim->clearMediaCollection('documents');
                $claim->delete();
            });

            // The listings stay public; they simply become unclaimed.
            Business::query()->where('owner_id', $user->id)->each(function (Business $business): void {
                $business->forceFill(['owner_id' => null, 'is_claimed' => false])->save();
            });

            if ($user->phone !== null) {
                OtpCode::query()->where('phone', $user->phone)->delete();
            }

            $user->notifications()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->roles()->detach();
            $user->permissions()->detach();

            // Foreign keys null the author of reviews, replies, reports, suggestions and
            // payments, and delete votes and favorites.
            $user->delete();

            activity('privacy')->withProperties(['user_id' => $user->id, 'reviews_removed' => $removeReviews])->log('account.deleted');
        });

        // Ratings change only when reviews were removed.
        Business::query()->whereIn('id', array_unique($affectedBusinessIds))->each(fn (Business $business) => $this->ranking->recalculate($business));
    }
}
