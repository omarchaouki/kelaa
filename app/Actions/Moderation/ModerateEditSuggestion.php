<?php

namespace App\Actions\Moderation;

use App\Actions\Owners\SuggestEdit;
use App\Enums\EditSuggestionStatus;
use App\Models\EditSuggestion;
use App\Models\User;
use App\Notifications\ContentRejected;
use App\Support\Moderation\ModerationGate;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ModerateEditSuggestion
{
    public function __construct(
        private readonly ModerateBusiness $businesses,
    ) {}

    /**
     * Applies the whitelisted changes through the model (normalization,
     * geo point, search index and cache all follow).
     *
     * @param  list<string>|null  $fields  Subset of the suggestion to apply (all by default).
     */
    public function apply(User $moderator, EditSuggestion $suggestion, ?array $fields = null): void
    {
        ModerationGate::asModerator($moderator, function () use ($moderator, $suggestion, $fields): void {
            DB::transaction(function () use ($moderator, $suggestion, $fields): void {
                $business = $suggestion->business()->lockForUpdate()->firstOrFail();
                $changes = array_intersect_key($suggestion->changes, array_flip(SuggestEdit::FIELDS));

                if ($fields !== null) {
                    $changes = array_intersect_key($changes, array_flip($fields));
                }

                $closed = (bool) ($changes['permanently_closed'] ?? false);
                unset($changes['permanently_closed']);

                $business->forceFill($changes + ['last_activity_at' => now()])->save();

                if ($closed) {
                    $this->businesses->suspend($moderator, $business, __('Établissement fermé définitivement (signalé par un utilisateur).'));
                }

                $suggestion->forceFill([
                    'status' => EditSuggestionStatus::Approved,
                    'reviewed_by' => $moderator->id,
                    'reviewed_at' => now(),
                ])->save();

                activity('moderation')->performedOn($business)->causedBy($moderator)
                    ->withProperties(['suggestion_id' => $suggestion->id, 'changes' => $changes, 'closed' => $closed])
                    ->log('edit_suggestion.applied');
            });
        });
    }

    public function reject(User $moderator, EditSuggestion $suggestion, string $reason): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required.');
        }

        ModerationGate::asModerator($moderator, function () use ($moderator, $suggestion, $reason): void {
            $suggestion->forceFill([
                'status' => EditSuggestionStatus::Rejected,
                'reviewed_by' => $moderator->id,
                'reviewed_at' => now(),
                'rejection_reason' => mb_substr($reason, 0, 255),
            ])->save();

            activity('moderation')->performedOn($suggestion)->causedBy($moderator)
                ->withProperties(['reason' => $reason])->log('edit_suggestion.rejected');
        });

        $suggestion->loadMissing('user');
        $suggestion->user?->notify(new ContentRejected('edit_suggestion', $suggestion->id, $reason));
    }
}
