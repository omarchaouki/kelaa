<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $business_id
 * @property int|null $user_id
 * @property string $amount
 * @property string $currency
 * @property PaymentMethod $method
 * @property string|null $reference
 * @property PaymentStatus $status
 * @property array<string, mixed>|null $gateway_payload
 * @property int|null $validated_by
 * @property Carbon|null $validated_at
 * @property string|null $rejection_reason
 * @property int|null $invoice_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * Status only changes through the payment validation action (admin only).
 */
#[Fillable(['business_id', 'user_id', 'amount', 'currency', 'method', 'reference'])]
#[Hidden(['gateway_payload'])]
class Payment extends Model implements HasMedia
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'gateway_payload' => 'array',
            'validated_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')
            ->singleFile()
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
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return HasOne<Subscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * @return HasOne<SponsoredPlacement, $this>
     */
    public function sponsoredPlacement(): HasOne
    {
        return $this->hasOne(SponsoredPlacement::class);
    }
}
