<?php

use App\Enums\Role;
use App\Models\City;

beforeEach(function () {
    City::factory()->create(['slug' => config('directory.default_city')]);
});

it('sends security headers on public pages', function () {
    $response = $this->get('/fr/'.config('directory.default_city'));

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');

    expect($response->headers->get('Permissions-Policy'))->toContain('geolocation=(self)', 'camera=()');
});

it('sends a nonce-based content security policy', function () {
    $response = $this->get('/fr/login');
    $csp = (string) $response->headers->get('Content-Security-Policy');

    preg_match("/'nonce-([^']+)'/", $csp, $matches);
    $nonce = $matches[1] ?? null;

    expect($nonce)->not->toBeNull()
        ->and($csp)->toContain("default-src 'self'", "object-src 'none'", "frame-ancestors 'none'", "base-uri 'self'", "form-action 'self'")
        ->and($csp)->not->toContain("script-src 'self' 'unsafe-inline'");

    // Every script tag on the page carries the nonce (Vite + Livewire).
    preg_match_all('/<script\b[^>]*>/i', $response->getContent(), $scripts);
    expect($scripts[0])->not->toBeEmpty();

    foreach ($scripts[0] as $tag) {
        expect($tag)->toContain("nonce=\"{$nonce}\"");
    }
});

it('uses a fresh nonce on every request', function () {
    $first = $this->get('/fr/login')->headers->get('Content-Security-Policy');
    $second = $this->get('/fr/login')->headers->get('Content-Security-Policy');

    expect($first)->not->toBe($second);
});

it('allows the configured map tile host for images', function () {
    config(['directory.maps.tile_url' => 'https://api.maptiler.com/maps/streets/{z}/{x}/{y}.png?key=abc']);

    expect($this->get('/fr/login')->headers->get('Content-Security-Policy'))->toContain('img-src', 'https://api.maptiler.com');
});

it('protects the admin panel with the same headers', function () {
    $response = $this->actingAs(staff(Role::Admin))->get('/admin');

    $response->assertOk()->assertHeader('X-Frame-Options', 'DENY');
    expect($response->headers->get('Content-Security-Policy'))->toContain("'nonce-");
});

it('sends HSTS only over HTTPS', function () {
    $this->get('/fr/login')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/fr/login')->assertHeader('Strict-Transport-Security');
});

it('throttles Livewire requests', function () {
    config(['security.rate_limits.livewire' => 2]);

    $route = collect(app('router')->getRoutes())->first(fn ($route) => str_ends_with((string) $route->getName(), 'livewire.update'));

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('throttle:livewire');
});
