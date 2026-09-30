<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Exceptions\UnauthorizedStatusChange;
use App\Support\Moderation\ModerationGate;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property int $business_id
 * @property int|null $user_id
 * @property int $rating
 * @property string|null $body
 * @property string|null $locale
 * @property ReviewStatus $status
 * @property list<string>|null $flags
 * @property int $helpful_count
 * @property Carbon|null $published_at
 * @property Carbon|null $edited_at
 * @property string|null $ip_hash
 * @property string|null $device_hash
 * @property int|null $moderated_by
 * @property Carbon|null $moderated_at
 * @property string|null $moderation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Status and moderation fields are deliberately not mass assignable:
 * they only change through the moderation actions.
 */
#[Fillable(['business_id', 'user_id', 'rating', 'body', 'locale'])]
#[Hidden(['ip_hash', 'device_hash'])]
class Review extends Model implements HasMedia
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory, InteractsWithMedia;

    protected static function booted(): void
    {
        // Hard rule: only moderation (App\Support\Moderation\ModerationGate)
        // may change the status of an existing review.
        static::updating(function (Review $review): void {
            if ($review->isDirty(['status', 'moderated_by', 'moderated_at', 'moderation_reason']) && ! ModerationGate::allowsStatusChange()) {
                throw UnauthorizedStatusChange::for('review');
            }
        });

        // Photos, votes, reports and revisions go with the review.
        static::deleting(function (Review $review): void {
            $review->reports()->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'flags' => 'array',
            'helpful_count' => 'integer',
            'published_at' => 'datetime',
            'edited_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->format('webp')
            ->fit(Fit::Crop, 240, 240);

        $this->addMediaConversion('large')
            ->format('webp')
            ->fit(Fit::Max, 1280, 1280);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * @return HasOne<ReviewReply, $this>
     */
    public function reply(): HasOne
    {
        return $this->hasOne(ReviewReply::class);
    }

    /**
     * @return HasMany<ReviewRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(ReviewRevision::class)->latest('id');
    }

    /**
     * @return HasMany<ReviewVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(ReviewVote::class);
    }

    /**
     * @return MorphMany<Report, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function isPublished(): bool
    {
        return $this->status === ReviewStatus::Published;
    }

    public function authorName(): string
    {
        return $this->user?->displayName() ?? __('Utilisateur supprimé');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ReviewStatus::Published);
    }
}
