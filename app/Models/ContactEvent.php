<?php

namespace App\Models;

use App\Enums\ContactEventSource;
use App\Enums\ContactEventType;
use Database\Factories\ContactEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $business_id
 * @property ContactEventType $type
 * @property ContactEventSource $source
 * @property int|null $sponsored_placement_id
 * @property string|null $session_hash
 * @property Carbon|null $created_at
 */
#[Fillable(['business_id', 'type', 'source', 'sponsored_placement_id', 'session_hash', 'created_at'])]
class ContactEvent extends Model
{
    /** @use HasFactory<ContactEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => ContactEventType::class,
            'source' => ContactEventSource::class,
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
     * @return BelongsTo<SponsoredPlacement, $this>
     */
    public function sponsoredPlacement(): BelongsTo
    {
        return $this->belongsTo(SponsoredPlacement::class);
    }
}
