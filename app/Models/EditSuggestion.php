<?php

namespace App\Models;

use App\Enums\EditSuggestionStatus;
use Database\Factories\EditSuggestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $business_id
 * @property int|null $user_id
 * @property bool $from_owner
 * @property array<string, mixed> $changes
 * @property string|null $comment
 * @property EditSuggestionStatus $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['business_id', 'user_id', 'changes', 'comment'])]
class EditSuggestion extends Model
{
    /** @use HasFactory<EditSuggestionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'from_owner' => 'boolean',
            'status' => EditSuggestionStatus::class,
            'reviewed_at' => 'datetime',
        ];
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
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
