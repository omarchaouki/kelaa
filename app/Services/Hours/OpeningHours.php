<?php

namespace App\Services\Hours;

use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\BusinessSpecialHour;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Opening hours of one business, evaluated in its city's timezone
 * (Africa/Casablanca). Handles split hours (several slots per day),
 * overnight slots (closing time earlier than opening time, "00:00" =
 * midnight), 24/7 businesses and special periods (Ramadan, Eid,
 * holidays) that replace the regular hours for the dates they cover.
 *
 * Business::scopeOpenAt() implements the same rules in SQL; a test keeps
 * both in agreement.
 */
final class OpeningHours
{
    /**
     * How far ahead to look for the next opening.
     */
    private const LOOKAHEAD_DAYS = 14;

    /**
     * @param  Collection<int, BusinessHour>  $regular
     * @param  Collection<int, BusinessSpecialHour>  $special
     */
    private function __construct(
        private readonly bool $alwaysOpen,
        private readonly Collection $regular,
        private readonly Collection $special,
        private readonly string $timezone,
    ) {}

    public static function for(Business $business): self
    {
        $business->loadMissing(['hours', 'specialHours', 'city']);

        return new self(
            $business->is_always_open,
            $business->hours,
            $business->specialHours,
            $business->city->timezone ?? config('directory.timezone'),
        );
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function statusAt(?CarbonInterface $moment = null): OpeningStatus
    {
        if ($this->alwaysOpen) {
            return OpeningStatus::alwaysOpen();
        }

        $now = CarbonImmutable::instance($moment ?? now())->setTimezone($this->timezone);

        // Yesterday's overnight slots may still be running.
        $slots = [...$this->slotsOn($now->subDay()), ...$this->slotsOn($now)];

        foreach ($slots as $slot) {
            if ($slot->contains($now)) {
                return new OpeningStatus(isOpen: true, closesAt: $this->chainedClosing($slot, $now), now: $now);
            }
        }

        return new OpeningStatus(isOpen: false, opensAt: $this->nextOpening($now), now: $now);
    }

    public function isOpenAt(?CarbonInterface $moment = null): bool
    {
        return $this->statusAt($moment)->isOpen;
    }

    /**
     * Opening slots starting on the given calendar date.
     *
     * @return list<OpeningSlot>
     */
    public function slotsOn(CarbonInterface $date): array
    {
        $day = CarbonImmutable::instance($date)->setTimezone($this->timezone)->startOfDay();
        $rows = $this->rowsFor($day);
        $slots = [];

        foreach ($rows as $row) {
            if ($row->is_closed || $row->opens_at === null || $row->closes_at === null) {
                continue;
            }

            $opens = $day->setTimeFromTimeString($row->opens_at);
            $closes = $day->setTimeFromTimeString($row->closes_at);

            if ($closes->lessThanOrEqualTo($opens)) {
                $closes = $closes->addDay();
            }

            $slots[] = new OpeningSlot($opens, $closes);
        }

        usort($slots, fn (OpeningSlot $a, OpeningSlot $b) => $a->opensAt <=> $b->opensAt);

        return $slots;
    }

    /**
     * Regular weekly schedule for display: [1 => [["09:00", "13:00"], ...], ..., 7 => []].
     *
     * @return array<int, list<array{0: string, 1: string}>>
     */
    public function weeklySchedule(): array
    {
        $week = array_fill_keys(range(1, 7), []);

        foreach ($this->regular->sortBy('opens_at') as $row) {
            if (! $row->is_closed && $row->opens_at !== null && $row->closes_at !== null) {
                $week[$row->day_of_week][] = [substr($row->opens_at, 0, 5), substr($row->closes_at, 0, 5)];
            }
        }

        return $week;
    }

    /**
     * Special periods that have not ended yet.
     *
     * @return Collection<int, BusinessSpecialHour>
     */
    public function upcomingSpecialPeriods(?CarbonInterface $moment = null): Collection
    {
        $today = CarbonImmutable::instance($moment ?? now())->setTimezone($this->timezone)->startOfDay();

        return $this->special
            ->filter(fn (BusinessSpecialHour $row) => $row->ends_on->greaterThanOrEqualTo($today))
            ->sortBy('starts_on')
            ->values();
    }

    /**
     * Rows that define the given date: special rows when a special period covers it.
     *
     * @return Collection<int, BusinessHour>|Collection<int, BusinessSpecialHour>
     */
    private function rowsFor(CarbonImmutable $day): Collection
    {
        $date = $day->toDateString();

        $special = $this->special->filter(
            fn (BusinessSpecialHour $row) => $row->starts_on->toDateString() <= $date && $row->ends_on->toDateString() >= $date,
        );

        if ($special->isNotEmpty()) {
            return $special;
        }

        return $this->regular->where('day_of_week', $day->isoWeekday());
    }

    /**
     * "Closes at" skips over back-to-back slots (e.g. 18:00-00:00 then 00:00-02:00).
     */
    private function chainedClosing(OpeningSlot $current, CarbonImmutable $now): CarbonImmutable
    {
        $closing = $current->closesAt;

        for ($i = 0; $i < 3; $i++) {
            $next = collect([...$this->slotsOn($closing->subDay()), ...$this->slotsOn($closing)])
                ->first(fn (OpeningSlot $slot) => $slot->opensAt->equalTo($closing) && $slot->closesAt->greaterThan($closing));

            if ($next === null) {
                break;
            }

            $closing = $next->closesAt;
        }

        return $closing;
    }

    private function nextOpening(CarbonImmutable $now): ?CarbonImmutable
    {
        for ($offset = 0; $offset <= self::LOOKAHEAD_DAYS; $offset++) {
            foreach ($this->slotsOn($now->addDays($offset)) as $slot) {
                if ($slot->opensAt->greaterThan($now)) {
                    return $slot->opensAt;
                }
            }
        }

        return null;
    }
}
