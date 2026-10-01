<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

it('reports every dependency as ok', function () {
    config(['scout.driver' => 'collection', 'queue.default' => 'sync']);

    $this->get('/health')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertExactJson(['status' => 'ok', 'checks' => ['database' => 'ok', 'cache' => 'ok']]);
});

it('returns 503 without leaking the error when a dependency fails', function () {
    Log::spy();
    Cache::shouldReceive('put')->andThrow(new RuntimeException('Connection refused to redis at 10.0.0.5:6379'));

    $response = $this->get('/health')->assertStatus(503);

    expect($response->json('status'))->toBe('fail')
        ->and($response->json('checks.cache'))->toBe('fail')
        ->and($response->getContent())->not->toContain('10.0.0.5');

    Log::shouldHaveReceived('warning')->once();
});

it('checks search and queue workers when they are configured', function () {
    config(['scout.driver' => 'meilisearch', 'queue.default' => 'redis']);

    $checks = $this->get('/health')->json('checks');

    // No Meilisearch, Redis or Horizon in the test environment: reported as failures, not errors.
    expect(array_keys($checks))->toBe(['database', 'cache', 'search', 'queue', 'workers'])
        ->and($checks['search'])->toBe('fail');
});

it('is rate limited', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->get('/health');
    }

    $this->get('/health')->assertStatus(429);
});
