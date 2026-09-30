<?php

namespace App\Models;

use App\Enums\ClaimMethod;
use App\Enums\ClaimStatus;
use Database\Factories\BusinessClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $business_id
 * @property int $user_id
 * @property ClaimMethod $method
 * @property string|null $notes
 * @property ClaimStatus $status
 * @property Carbon|null $phone_verified_at
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['business_id', 'user_id', 'method', 'notes'])]
class BusinessClaim extends Model implements HasMedia
{
    /** @use HasFactory<BusinessClaimFactory> */
    use HasFactory, InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'method' => ClaimMethod::class,
            'status' => ClaimStatus::class,
            'phone_verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        // Registre de commerce, patente, CIN: never on a public disk.
        $this->addMediaCollection('documents')
            ->useDisk('local')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
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
