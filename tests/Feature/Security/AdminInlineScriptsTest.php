<?php

use App\Enums\Role;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;

/**
 * Filament renders a few inline <script> tags without our CSP nonce. On admin
 * pages they are allowed by their exact SHA-256 hash (config/security.php),
 * so nothing else inline can run. This test fails when a Filament update
 * changes those scripts: copy the new hashes it prints into the config.
 *
 * @return list<string>
 */
function unnoncedInlineScriptHashes(string $html): array
{
    preg_match_all('#<script(?![^>]*\b(?:src|nonce)=)(?![^>]*type="application/ld\+json")[^>]*>(.*?)</script>#s', $html, $matches);

    return array_values(array_unique(array_map(
        fn (string $body) => "'sha256-".base64_encode(hash('sha256', $body, true))."'",
        array_filter($matches[1], fn (string $body) => trim($body) !== ''),
    )));
}

it('allows every Filament inline script by hash, and nothing more', function () {
    $city = City::factory()->create(['slug' => 'ma-ville']);
    $business = Business::factory()->for($city)->inCategory(Category::factory()->create())->create();
    $hashes = unnoncedInlineScriptHashes($this->get('/admin/login')->getContent());

    $this->actingAs(staff(Role::Admin));

    foreach (['/admin', '/admin/businesses', "/admin/businesses/{$business->slug}/edit", '/admin/placement-calendar', '/admin/users', '/admin/reviews', '/admin/payments', '/admin/categories', '/admin/pages'] as $url) {
        $hashes = [...$hashes, ...unnoncedInlineScriptHashes($this->get($url)->assertOk()->getContent())];
    }

    $hashes = array_values(array_unique($hashes));
    $allowed = config('security.csp.admin_script_hashes');

    expect(array_values(array_diff($hashes, $allowed)))->toBe([], 'New Filament inline scripts, add to security.csp.admin_script_hashes: '.implode(' ', array_diff($hashes, $allowed)));
});

it('sends those hashes on admin pages only', function () {
    $admin = $this->actingAs(staff(Role::Admin))->get('/admin')->headers->get('Content-Security-Policy');
    $public = $this->get('/robots.txt')->headers->get('Content-Security-Policy');

    foreach (config('security.csp.admin_script_hashes') as $hash) {
        expect($admin)->toContain($hash)
            ->and((string) $public)->not->toContain($hash);
    }

    preg_match('/script-src [^;]*/', (string) $admin, $scriptSrc);

    expect($scriptSrc[0])->not->toContain("'unsafe-inline'");
});
