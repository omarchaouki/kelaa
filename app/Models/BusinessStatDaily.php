<?php

namespace App\Models;

use App\Enums\ContactEventSource;
use App\Enums\ContactEventType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $business_id
 * @property Carbon $date
 * @property ContactEventType $type
 * @property ContactEventSource $source
 * @property int $count
 * @property int $unique_sessions
 */
#[Table(name: 'business_stats_daily', timestamps: false)]
#[Fillable(['business_id', 'date', 'type', 'source', 'count', 'unique_sessions'])]
class BusinessStatDaily extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => ContactEventType::class,
            'source' => ContactEventSource::class,
            'count' => 'integer',
            'unique_sessions' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
