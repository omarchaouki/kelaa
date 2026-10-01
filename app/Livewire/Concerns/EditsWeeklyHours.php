<?php

namespace App\Livewire\Concerns;

use App\Models\Business;
use App\Support\Hours\TimeOfDay;
use App\Support\Hours\WeeklyHours;
use InvalidArgumentException;

/**
 * Weekly hours editor state: day => ['closed' => bool, 'slots' => [['09:00', '13:00'], ...]].
 */
trait EditsWeeklyHours
{
    /**
     * Shape: day => ['closed' => bool, 'slots' => [['09:00', '13:00'], ...]]. Comes back from the
     * browser on every request, so it is treated as untrusted (see validatedWeek()).
     *
     * @var array<int|string, mixed>
     */
    public array $week = [];

    protected function defaultWeek(): void
    {
        $this->week = [];

        foreach (range(1, 7) as $day) {
            $this->week[$day] = ['closed' => $day === 7, 'slots' => [['09:00', '19:00']]];
        }
    }

    protected function weekFrom(Business $business): void
    {
        $business->loadMissing('hours');
        $this->week = [];

        foreach (range(1, 7) as $day) {
            $slots = $business->hours
                ->where('day_of_week', $day)
                ->where('is_closed', false)
                ->map(fn ($hour) => [substr((string) $hour->opens_at, 0, 5), substr((string) $hour->closes_at, 0, 5)])
                ->values()
                ->all();

            $this->week[$day] = ['closed' => $slots === [], 'slots' => $slots === [] ? [['09:00', '19:00']] : $slots];
        }
    }

    public function addSlot(int $day): void
    {
        if (isset($this->week[$day]['slots']) && is_array($this->week[$day]['slots']) && count($this->week[$day]['slots']) < WeeklyHours::MAX_SLOTS_PER_DAY) {
            $this->week[$day]['slots'][] = ['15:00', '19:00'];
        }
    }

    public function removeSlot(int $day, int $index): void
    {
        if (isset($this->week[$day]['slots'][$index]) && is_array($this->week[$day]['slots']) && count($this->week[$day]['slots']) > 1) {
            unset($this->week[$day]['slots'][$index]);
            $this->week[$day]['slots'] = array_values($this->week[$day]['slots']);
        }
    }

    /**
     * Validated week for persistence: ISO day => [[opens, closes], ...] (closed days omitted).
     *
     * @return array<int, list<array{0: string, 1: string}>>
     */
    protected function validatedWeek(): array
    {
        $result = [];

        foreach (range(1, 7) as $day) {
            $entry = $this->week[$day] ?? null;

            if (! is_array($entry) || ($entry['closed'] ?? true)) {
                continue;
            }

            foreach (array_slice((array) ($entry['slots'] ?? []), 0, WeeklyHours::MAX_SLOTS_PER_DAY) as $index => $slot) {
                try {
                    $opens = TimeOfDay::normalize(is_string($slot[0] ?? null) ? $slot[0] : null);
                    $closes = TimeOfDay::normalize(is_string($slot[1] ?? null) ? $slot[1] : null);
                } catch (InvalidArgumentException) {
                    $opens = $closes = null;
                }

                if ($opens === null || $closes === null || $opens === $closes) {
                    $this->addError("week.{$day}.slots.{$index}", __('Horaire invalide.'));

                    continue;
                }

                $result[$day][] = [substr($opens, 0, 5), substr($closes, 0, 5)];
            }
        }

        return $result;
    }
}
