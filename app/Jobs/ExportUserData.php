<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\EditSuggestion;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\ReviewVote;
use App\Models\User;
use App\Notifications\DataExportReady;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds the JSON copy of everything we hold about a user (law 09-08 right of
 * access) on the private disk, then sends a temporary download link.
 */
class ExportUserData implements ShouldQueue
{
    use Queueable;

    public const DIRECTORY = 'exports';

    public const LIFETIME_DAYS = 7;

    public function __construct(public int $userId) {}

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            return;  // deleted in the meantime
        }

        $token = Str::random(40);
        Storage::disk('local')->put(self::path($user->id, $token), json_encode($this->collect($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        activity('privacy')->causedBy($user)->log('account.exported');
        $user->notify(new DataExportReady($token, now()->addDays(self::LIFETIME_DAYS)));
    }

    public static function path(int $userId, string $token): string
    {
        return self::DIRECTORY."/{$userId}/{$token}.json";
    }

    /**
     * @return array<string, mixed>
     */
    public function collect(User $user): array
    {
        $date = fn ($value) => $value?->toIso8601String();

        return [
            'generated_at' => now()->toIso8601String(),
            'account' => [
                'name' => $user->name,
                'phone' => $user->phone,
                'phone_verified_at' => $date($user->phone_verified_at),
                'email' => $user->email,
                'email_verified_at' => $date($user->email_verified_at),
                'google_account_linked' => $user->google_id !== null,
                'locale' => $user->locale,
                'roles' => $user->getRoleNames()->values()->all(),
                'created_at' => $date($user->created_at),
                'last_login_at' => $date($user->last_login_at),
                'banned_at' => $date($user->banned_at),
                'ban_reason' => $user->ban_reason,
            ],
            'reviews' => Review::query()->where('user_id', $user->id)->with(['business:id,name', 'revisions', 'media'])->orderBy('id')->get()
                ->map(fn (Review $review) => [
                    'business' => $review->business?->name,
                    'rating' => $review->rating,
                    'body' => $review->body,
                    'status' => $review->status->value,
                    'created_at' => $date($review->created_at),
                    'published_at' => $date($review->published_at),
                    'edited_at' => $date($review->edited_at),
                    'photos' => $review->getMedia('photos')->count(),
                    'previous_versions' => $review->revisions->map(fn ($revision) => [
                        'rating' => $revision->rating,
                        'body' => $revision->body,
                        'created_at' => $date($revision->created_at),
                    ])->all(),
                ])->all(),
            'replies' => ReviewReply::query()->where('user_id', $user->id)->orderBy('id')->get()
                ->map(fn (ReviewReply $reply) => [
                    'review_id' => $reply->review_id,
                    'body' => $reply->body,
                    'status' => $reply->status->value,
                    'created_at' => $date($reply->created_at),
                ])->all(),
            'helpful_votes' => ReviewVote::query()->where('user_id', $user->id)->orderBy('id')->get()
                ->map(fn (ReviewVote $vote) => ['review_id' => $vote->review_id, 'created_at' => $date($vote->created_at)])->all(),
            'favorites' => $user->favorites()->orderBy('favorites.created_at')->get(['businesses.id', 'businesses.name'])
                ->map(fn (Business $business) => ['business' => $business->name])->all(),
            'reports' => Report::query()->where('reporter_id', $user->id)->orderBy('id')->get()
                ->map(fn (Report $report) => [
                    'reason' => $report->reason->value,
                    'details' => $report->details,
                    'status' => $report->status->value,
                    'created_at' => $date($report->created_at),
                ])->all(),
            'claims' => BusinessClaim::query()->where('user_id', $user->id)->with('business:id,name')->orderBy('id')->get()
                ->map(fn (BusinessClaim $claim) => [
                    'business' => $claim->business?->name,
                    'method' => $claim->method->value,
                    'status' => $claim->status->value,
                    'created_at' => $date($claim->created_at),
                ])->all(),
            'edit_suggestions' => EditSuggestion::query()->where('user_id', $user->id)->with('business:id,name')->orderBy('id')->get()
                ->map(fn (EditSuggestion $suggestion) => [
                    'business' => $suggestion->business?->name,
                    'changes' => $suggestion->changes,
                    'status' => $suggestion->status->value,
                    'created_at' => $date($suggestion->created_at),
                ])->all(),
            'businesses_owned' => $user->ownedBusinesses()->orderBy('id')->get(['id', 'name', 'legal_name', 'ice', 'legal_address'])
                ->map(fn (Business $business) => $business->only(['name', 'legal_name', 'ice', 'legal_address']))->all(),
            'payments' => Payment::query()->where('user_id', $user->id)->with('invoice:id,number')->orderBy('id')->get()
                ->map(fn (Payment $payment) => [
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'method' => $payment->method->value,
                    'status' => $payment->status->value,
                    'invoice' => $payment->invoice?->number,
                    'created_at' => $date($payment->created_at),
                ])->all(),
            'notifications' => $user->notifications()->latest()->limit(500)->get()
                ->map(fn ($notification) => [
                    'type' => class_basename($notification->type),
                    'created_at' => $date($notification->created_at),
                    'read_at' => $date($notification->read_at),
                ])->all(),
        ];
    }
}
