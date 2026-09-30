<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $review_id
 * @property int $rating
 * @property string|null $body
 * @property Carbon|null $created_at
 *
 * Snapshot of a review before it was edited.
 */
#[Fillable(['review_id', 'rating', 'body', 'created_at'])]
class ReviewRevision extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Review, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
