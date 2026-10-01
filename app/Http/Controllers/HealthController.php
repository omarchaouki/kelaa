<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Meilisearch\Client;
use RuntimeException;
use Throwable;

/**
 * Uptime probe: each dependency is "ok" or "fail", nothing more. Errors are
 * logged, never returned, so the endpoint reveals no infrastructure details.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check('database', fn () => DB::select('select 1')),
            'cache' => $this->check('cache', function () {
                $key = 'health:'.Str::random(12);
                Cache::put($key, 'ok', 10);
                $value = Cache::pull($key);

                return $value === 'ok' ?: throw new RuntimeException('Cache read mismatch.');
            }),
        ];

        if (config('scout.driver') === 'meilisearch') {
            $checks['search'] = $this->check('search', fn () => app(Client::class)->health());
        }

        if (config('queue.default') === 'redis') {
            $checks['queue'] = $this->check('queue', fn () => Queue::connection('redis')->size());
            $checks['workers'] = $this->check('workers', function () {
                if (! app(MasterSupervisorRepository::class)->all()) {
                    throw new RuntimeException('Horizon is not running.');
                }

                return true;
            });
        }

        $healthy = ! in_array('fail', $checks, true);

        return response()->json(['status' => $healthy ? 'ok' : 'fail', 'checks' => $checks], $healthy ? 200 : 503, [
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * @param  callable(): mixed  $probe
     */
    private function check(string $name, callable $probe): string
    {
        try {
            $probe();

            return 'ok';
        } catch (Throwable $e) {
            Log::warning("Health check failed: {$name}", ['exception' => $e::class, 'message' => $e->getMessage()]);

            return 'fail';
        }
    }
}
