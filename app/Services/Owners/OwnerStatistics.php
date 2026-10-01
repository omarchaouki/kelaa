<?php

namespace App\Services\Owners;

use App\Enums\ContactEventSource;
use App\Enums\ContactEventType;
use App\Models\Business;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Statistics shown to owners: daily aggregates (business_stats_daily)
 * plus today's raw events, which the nightly job has not rolled up yet.
 */
class OwnerStatistics
{
    public const PERIODS = ['7d' => 7, '30d' => 30, '90d' => 90];

    /**
     * @return array{
     *     from: CarbonImmutable,
     *     to: CarbonImmutable,
     *     totals: array<string, int>,
     *     by_source: array<string, int>,
     *     daily: array<string, array<string, int>>,
     *     contacts: int
     * }
     */
    public function summary(Business $business, string $period = '30d'): array
    {
        $days = self::PERIODS[$period] ?? 30;
        $to = now()->toImmutable()->startOfDay();
        $from = $to->subDays($days - 1);

        $rows = DB::table('business_stats_daily')
            ->where('business_id', $business->id)
            ->whereBetween('date', [$from->toDateString(), $to->subDay()->toDateString()])
            ->get(['date', 'type', 'source', 'count'])
            ->map(fn ($row) => ['date' => substr((string) $row->date, 0, 10), 'type' => $row->type, 'source' => $row->source, 'count' => (int) $row->count]);

        $today = DB::table('contact_events')
            ->where('business_id', $business->id)
            ->where('created_at', '>=', $to)
            ->groupBy('type', 'source')
            ->selectRaw('type, source, count(*) as total')
            ->get()
            ->map(fn ($row) => ['date' => $to->toDateString(), 'type' => $row->type, 'source' => $row->source, 'count' => (int) $row->total]);

        $all = $rows->concat($today);

        $types = array_map(fn (ContactEventType $type) => $type->value, ContactEventType::cases());
        $daily = [];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $daily[$date->toDateString()] = array_fill_keys($types, 0);
        }

        $totals = array_fill_keys($types, 0);
        $bySource = array_fill_keys(array_map(fn (ContactEventSource $s) => $s->value, ContactEventSource::cases()), 0);

        foreach ($all as $row) {
            if (! isset($daily[$row['date']][$row['type']])) {
                continue;
            }

            $daily[$row['date']][$row['type']] += $row['count'];
            $totals[$row['type']] += $row['count'];
            $bySource[$row['source']] = ($bySource[$row['source']] ?? 0) + $row['count'];
        }

        return [
            'from' => $from,
            'to' => $to,
            'totals' => $totals,
            'by_source' => $bySource,
            'daily' => $daily,
            'contacts' => $totals['call_click'] + $totals['whatsapp_click'] + $totals['directions_click'] + $totals['website_click'],
        ];
    }
}
