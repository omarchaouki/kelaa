<?php

use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\BusinessSpecialHour;
use App\Models\City;
use App\Services\Hours\OpeningHours;
use Carbon\CarbonImmutable;

// Monday 2026-10-05 in Casablanca.
const MONDAY = '2026-10-05';

function at(string $date, string $time): CarbonImmutable
{
    return CarbonImmutable::parse("{$date} {$time}", 'Africa/Casablanca');
}

function withSlots(City $city, array $slots, array $attributes = []): Business
{
    $business = Business::factory()->for($city)->create($attributes);

    foreach ($slots as [$day, $opens, $closes]) {
        BusinessHour::query()->create(['business_id' => $business->id, 'day_of_week' => $day, 'opens_at' => $opens, 'closes_at' => $closes]);
    }

    return $business->fresh();
}

beforeEach(function () {
    $this->city = City::factory()->create(['timezone' => 'Africa/Casablanca']);

    // Mon-Sat 09:00-13:00 and 15:00-20:00, Sunday closed.
    $split = [];
    foreach (range(1, 6) as $day) {
        $split[] = [$day, '09:00', '13:00'];
        $split[] = [$day, '15:00', '20:00'];
    }
    $this->split = withSlots($this->city, $split, ['name' => 'Split']);

    // Friday night 20:00 to Saturday 02:00.
    $this->overnight = withSlots($this->city, [[5, '20:00', '02:00']], ['name' => 'Overnight']);

    // Every day 18:00 to midnight.
    $this->midnight = withSlots($this->city, array_map(fn ($d) => [$d, '18:00', '00:00'], range(1, 7)), ['name' => 'Midnight']);

    $this->alwaysOpen = Business::factory()->for($this->city)->create(['name' => 'AllDay', 'is_always_open' => true]);

    // Regular 09:00-20:00 every day, Ramadan 10:00-16:00 on Oct 7-9, closed for Eid on Oct 10.
    $this->ramadan = withSlots($this->city, array_map(fn ($d) => [$d, '09:00', '20:00'], range(1, 7)), ['name' => 'Ramadan']);
    BusinessSpecialHour::query()->create(['business_id' => $this->ramadan->id, 'starts_on' => '2026-10-07', 'ends_on' => '2026-10-09', 'opens_at' => '10:00', 'closes_at' => '16:00']);
    BusinessSpecialHour::query()->create(['business_id' => $this->ramadan->id, 'starts_on' => '2026-10-10', 'ends_on' => '2026-10-10', 'is_closed' => true]);
});

it('handles split hours', function () {
    $hours = OpeningHours::for($this->split);

    $open = $hours->statusAt(at(MONDAY, '10:00'));
    expect($open->isOpen)->toBeTrue()
        ->and($open->closesAt->format('H:i'))->toBe('13:00');

    $lunch = $hours->statusAt(at(MONDAY, '14:00'));
    expect($lunch->isOpen)->toBeFalse()
        ->and($lunch->opensAt->format('Y-m-d H:i'))->toBe('2026-10-05 15:00')
        ->and($lunch->label())->toBe('Fermé · ouvre à 15:00');

    $evening = $hours->statusAt(at(MONDAY, '21:00'));
    expect($evening->opensAt->format('Y-m-d H:i'))->toBe('2026-10-06 09:00')
        ->and($evening->label())->toBe('Fermé · ouvre demain à 09:00');
});

it('skips closed days when looking for the next opening', function () {
    // Saturday evening: Sunday is closed, next opening is Monday.
    $status = OpeningHours::for($this->split)->statusAt(at('2026-10-10', '21:00'));

    expect($status->opensAt->format('Y-m-d H:i'))->toBe('2026-10-12 09:00')
        ->and($status->label())->toBe('Fermé · ouvre lundi à 09:00');
});

it('treats the closing time as exclusive and the opening time as inclusive', function () {
    $hours = OpeningHours::for($this->split);

    expect($hours->isOpenAt(at(MONDAY, '09:00')))->toBeTrue()
        ->and($hours->isOpenAt(at(MONDAY, '13:00')))->toBeFalse()
        ->and($hours->isOpenAt(at(MONDAY, '19:59:59')))->toBeTrue();
});

it('handles slots running past midnight', function () {
    $hours = OpeningHours::for($this->overnight);

    $afterMidnight = $hours->statusAt(at('2026-10-10', '01:00')); // Saturday 01:00
    expect($afterMidnight->isOpen)->toBeTrue()
        ->and($afterMidnight->closesAt->format('Y-m-d H:i'))->toBe('2026-10-10 02:00')
        ->and($hours->isOpenAt(at('2026-10-10', '02:30')))->toBeFalse()
        ->and($hours->isOpenAt(at('2026-10-09', '19:00')))->toBeFalse()
        ->and($hours->isOpenAt(at('2026-10-09', '23:00')))->toBeTrue();
});

it('reads a 00:00 closing time as midnight', function () {
    $status = OpeningHours::for($this->midnight)->statusAt(at(MONDAY, '23:30'));

    expect($status->isOpen)->toBeTrue()
        ->and($status->closesAt->format('Y-m-d H:i'))->toBe('2026-10-06 00:00')
        ->and(OpeningHours::for($this->midnight)->isOpenAt(at(MONDAY, '00:30')))->toBeFalse();
});

it('keeps 24/7 businesses open', function () {
    $status = OpeningHours::for($this->alwaysOpen)->statusAt(at(MONDAY, '04:00'));

    expect($status->isOpen)->toBeTrue()->and($status->alwaysOpen)->toBeTrue()
        ->and($status->label())->toBe('Ouvert 24h/24');
});

it('lets special periods replace the regular hours', function () {
    $hours = OpeningHours::for($this->ramadan);

    expect($hours->isOpenAt(at('2026-10-08', '09:30')))->toBeFalse()   // regular would be open
        ->and($hours->isOpenAt(at('2026-10-08', '11:00')))->toBeTrue()
        ->and($hours->isOpenAt(at('2026-10-08', '17:00')))->toBeFalse()   // regular would be open
        ->and($hours->isOpenAt(at('2026-10-10', '12:00')))->toBeFalse()   // Eid: closed all day
        ->and($hours->isOpenAt(at('2026-10-11', '12:00')))->toBeTrue()    // back to regular
        ->and($hours->statusAt(at('2026-10-09', '17:00'))->opensAt->format('Y-m-d H:i'))->toBe('2026-10-11 09:00');
});

it('evaluates hours in the city timezone whatever the input timezone', function () {
    // 09:30 UTC is 10:30 in Casablanca (UTC+1).
    $moment = CarbonImmutable::parse('2026-10-05 09:30', 'UTC');

    expect(OpeningHours::for($this->split)->isOpenAt($moment))->toBeTrue();
});

it('builds the weekly schedule for display', function () {
    $week = OpeningHours::for($this->split)->weeklySchedule();

    expect($week[1])->toBe([['09:00', '13:00'], ['15:00', '20:00']])
        ->and($week[7])->toBe([]);
});

it('agrees with the SQL "open at" scope', function (string $date, string $time) {
    $moment = at($date, $time);

    $sql = Business::query()->openAt($moment)->orderBy('id')->pluck('id')->all();
    $php = Business::query()->with(['hours', 'specialHours', 'city'])->orderBy('id')->get()
        ->filter(fn (Business $b) => OpeningHours::for($b)->isOpenAt($moment))
        ->pluck('id')->values()->all();

    expect($sql)->toBe($php);
})->with([
    'monday morning' => [MONDAY, '10:00'],
    'monday lunch' => [MONDAY, '14:00'],
    'monday opening second' => [MONDAY, '09:00:00'],
    'monday closing second' => [MONDAY, '13:00:00'],
    'monday late' => [MONDAY, '23:30'],
    'friday night' => ['2026-10-09', '23:00'],
    'saturday after midnight' => ['2026-10-10', '01:00'],
    'saturday 03:00' => ['2026-10-10', '03:00'],
    'ramadan morning' => ['2026-10-08', '09:30'],
    'ramadan midday' => ['2026-10-08', '11:00'],
    'eid' => ['2026-10-10', '12:00'],
    'sunday' => ['2026-10-11', '12:00'],
]);

it('normalizes stored times and rejects invalid ones', function () {
    $hour = BusinessHour::query()->create(['business_id' => $this->split->id, 'day_of_week' => 7, 'opens_at' => '9:5', 'closes_at' => '24:00']);
})->throws(InvalidArgumentException::class);

it('stores times as HH:MM:SS', function () {
    $hour = BusinessHour::query()->create(['business_id' => $this->split->id, 'day_of_week' => 7, 'opens_at' => '9:30', 'closes_at' => '24:00']);

    expect($hour->fresh()->opens_at)->toBe('09:30:00')
        ->and($hour->fresh()->closes_at)->toBe('00:00:00');
});

it('refuses an invalid day of week', function () {
    BusinessHour::query()->create(['business_id' => $this->split->id, 'day_of_week' => 8, 'opens_at' => '09:00', 'closes_at' => '10:00']);
})->throws(InvalidArgumentException::class);
