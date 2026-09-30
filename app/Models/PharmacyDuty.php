<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\PharmacyDutyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $business_id
 * @property Carbon $date
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * A pharmacie de garde shift (usually an evening/night or a full weekend day).
 */
#[Table(name: 'pharmacies_de_garde')]
#[Fillable(['business_id', 'date', 'starts_at', 'ends_at'])]
class PharmacyDuty extends Model
{
    /** @use HasFactory<PharmacyDutyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function activeAt(Builder $query, CarbonInterface $moment): void
    {
        $query->where('starts_at', '<=', $moment)->where('ends_at', '>', $moment);
    }
}
