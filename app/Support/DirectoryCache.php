<?php

namespace App\Support;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Cache for directory pages (category counts, neighborhoods, on-duty
 * pharmacies). Keys embed a version number: flushing bumps the version, so
 * every entry is invalidated at once on any cache store (no tags needed).
 */
final class DirectoryCache
{
    private const VERSION_KEY = 'directory:version';

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function remember(string $key, DateTimeInterface|DateInterval|int $ttl, Closure $callback): mixed
    {
        return Cache::remember(self::key($key), $ttl, $callback);
    }

    public static function flush(): void
    {
        Cache::increment(self::VERSION_KEY) ?: Cache::forever(self::VERSION_KEY, 2);
    }

    private static function key(string $key): string
    {
        $version = (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);

        return "directory:v{$version}:{$key}";
    }
}
