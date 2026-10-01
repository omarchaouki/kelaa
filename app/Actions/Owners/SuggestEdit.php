<?php

namespace App\Actions\Owners;

use App\Actions\Reviews\SubmitReview;
use App\Enums\EditSuggestionStatus;
use App\Exceptions\RateLimited;
use App\Models\Business;
use App\Models\EditSuggestion;
use App\Models\User;
use App\Rules\MoroccanPhoneNumber;
use App\Support\Phone\MoroccanPhone;
use App\Support\Security\SafeUrl;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Proposed corrections to a listing. Nothing changes until a moderator
 * applies them. Used by visitors ("suggest an edit") and by owners for
 * identity fields (name, address, position, phone numbers).
 */
class SuggestEdit
{
    /**
     * The only fields a suggestion may touch.
     */
    public const FIELDS = ['name', 'phone', 'whatsapp', 'email', 'website', 'address', 'neighborhood_id', 'latitude', 'longitude', 'permanently_closed'];

    /**
     * @param  array<string, mixed>  $changes
     *
     * @throws AuthorizationException
     * @throws RateLimited
     * @throws ValidationException
     */
    public function __invoke(User $user, Business $business, array $changes, ?string $comment = null): ?EditSuggestion
    {
        if ($user->isBanned() || ! $user->hasVerifiedPhone()) {
            throw new AuthorizationException(__('Un numéro de mobile vérifié est nécessaire.'));
        }

        $changes = array_intersect_key($changes, array_flip(self::FIELDS));

        $validated = Validator::make($changes, [
            'name' => ['sometimes', 'string', 'min:2', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', MoroccanPhoneNumber::any()],
            'whatsapp' => ['sometimes', 'nullable', 'string', MoroccanPhoneNumber::mobile()],
            'email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'website' => ['sometimes', 'nullable', 'string', 'max:255', fn ($attribute, $value, $fail) => $value === null || SafeUrl::isSafe($value) ? null : $fail(__('Adresse web invalide.'))],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'neighborhood_id' => ['sometimes', 'nullable', 'integer', Rule::exists('neighborhoods', 'id')->where('city_id', $business->city_id)],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'permanently_closed' => ['sometimes', 'boolean'],
        ])->validate();

        // Keep only real differences, stored in their normalized form.
        $normalized = [];

        foreach ($validated as $field => $value) {
            $value = match ($field) {
                'phone' => MoroccanPhone::normalize($value, mobileOnly: false),
                'whatsapp' => MoroccanPhone::normalize($value),
                'website' => SafeUrl::normalize($value),
                'name', 'address' => is_string($value) ? trim(SubmitReview::cleanBody($value) ?? '') : $value,
                'latitude', 'longitude' => round((float) $value, 7),
                'neighborhood_id' => $value === null ? null : (int) $value,
                'permanently_closed' => (bool) $value,
                default => $value,
            };

            $current = $field === 'permanently_closed' ? false : $business->getAttribute($field);

            if ($value !== $current && ! ($field === 'permanently_closed' && $value === false)) {
                $normalized[$field] = $value;
            }
        }

        if ($normalized === []) {
            return null;
        }

        RateLimited::guard('suggest-edit:'.$user->id, (int) config('owners.suggestions_per_day'), 86400);

        $suggestion = new EditSuggestion([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'changes' => $normalized,
            'comment' => $comment === null ? null : mb_substr((string) SubmitReview::cleanBody($comment), 0, 1000),
        ]);
        $suggestion->forceFill([
            'status' => EditSuggestionStatus::Pending,
            'from_owner' => $business->owner_id === $user->id,
        ])->save();

        return $suggestion;
    }
}
