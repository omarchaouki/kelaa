<?php

namespace App\Jobs;

use App\Models\BusinessStatDaily;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Rolls one day of contact events up into business_stats_daily.
 * Idempotent: re-running a day replaces its rows.
 */
class AggregateContactEvents implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $date,
    ) {}

    public static function forYesterday(): self
    {
        return new self(now()->subDay()->toDateString());
    }

    public function handle(): void
    {
        $day = CarbonImmutable::parse($this->date)->startOfDay();

        $rows = DB::table('contact_events')
            ->where('created_at', '>=', $day)
            ->where('created_at', '<', $day->addDay())
            ->groupBy('business_id', 'type', 'source')
            ->select([
                'business_id',
                'type',
                'source',
                DB::raw('count(*) as total'),
                DB::raw('count(distinct session_hash) as unique_sessions'),
            ])
            ->get();

        DB::transaction(function () use ($rows, $day): void {
            BusinessStatDaily::query()->where('date', $day->toDateString())->delete();

            foreach ($rows->chunk(500) as $chunk) {
                BusinessStatDaily::query()->insert($chunk->map(fn ($row) => [
                    'business_id' => $row->business_id,
                    'date' => $day->toDateString(),
                    'type' => $row->type,
                    'source' => $row->source,
                    'count' => $row->total,
                    'unique_sessions' => $row->unique_sessions,
                ])->all());
            }
        });
    }
}
