<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use LogicException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property string $number
 * @property int $year
 * @property int $sequence
 * @property int|null $business_id
 * @property string $client_name
 * @property string|null $client_ice
 * @property string|null $client_address
 * @property list<array<string, mixed>> $lines
 * @property string $subtotal
 * @property string $tax_rate
 * @property string $tax_amount
 * @property string $total
 * @property string $currency
 * @property Carbon $issued_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'number', 'year', 'sequence', 'business_id', 'client_name', 'client_ice', 'client_address',
    'lines', 'subtotal', 'tax_rate', 'tax_amount', 'total', 'currency', 'issued_at',
])]
class Invoice extends Model implements HasMedia
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * An issued invoice is a legal document: it is never edited nor deleted.
     * Corrections go through a refund (and a credit note outside the app).
     */
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Issued invoices are immutable.'));
        static::deleting(fn () => throw new LogicException('Issued invoices cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'sequence' => 'integer',
            'lines' => 'array',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('pdf')->singleFile()->useDisk('local');
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
