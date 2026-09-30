<?php

namespace App\Models;

use App\Enums\ReplyStatus;
use Database\Factories\ReviewReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $review_id
 * @property int|null $user_id
 * @property string $body
 * @property ReplyStatus $status
 * @property Carbon|null $edited_at
 * @property int|null $moderated_by
 * @property Carbon|null $moderated_at
 * @property string|null $moderation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['review_id', 'user_id', 'body'])]
class ReviewReply extends Model
{
    /** @use HasFactory<ReviewReplyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ReplyStatus::class,
            'edited_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Review, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphMany<Report, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
