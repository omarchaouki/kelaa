<?php

namespace App\Support\Hours;

use InvalidArgumentException;

final class TimeOfDay
{
    /**
     * "9:00", "09:00", "09:00:00" => "09:00:00". "24:00" (end of day) => "00:00:00",
     * which the engine reads as midnight when used as a closing time.
     */
    public static function normalize(?string $time): ?string
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        if (! preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($time), $m)) {
            throw new InvalidArgumentException("Invalid time [{$time}].");
        }

        [$hours, $minutes, $seconds] = [(int) $m[1], (int) $m[2], (int) ($m[3] ?? 0)];

        if ($hours === 24 && $minutes === 0 && $seconds === 0) {
            return '00:00:00';
        }

        if ($hours > 23 || $minutes > 59 || $seconds > 59) {
            throw new InvalidArgumentException("Invalid time [{$time}].");
        }

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Seconds since midnight.
     */
    public static function toSeconds(string $time): int
    {
        [$h, $m, $s] = array_map('intval', explode(':', (string) self::normalize($time)));

        return $h * 3600 + $m * 60 + $s;
    }
}
