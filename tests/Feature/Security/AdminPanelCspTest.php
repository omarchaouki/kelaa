<?php

use App\Enums\Role;
use App\Filament\AvatarProviders\InitialsAvatarProvider;

/**
 * Filament's inline scripts carry no nonce and are blocked by our CSP. The
 * panel must still get a valid sidebar state (otherwise the menu is empty)
 * and dark mode, through our own nonce'd bootstrap script.
 */
it('boots the admin panel with a nonce\'d script that matches the CSP', function () {
    $response = $this->actingAs(staff(Role::Admin))->get('/admin')->assertOk();

    preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $header);
    preg_match('/<script nonce="([^"]+)">\s*\(\(\) => \{\s*let groups/', $response->getContent(), $script);

    expect($header[1] ?? null)->not->toBeNull()
        ->and($script[1] ?? null)->toBe($header[1])
        ->and($response->getContent())->toContain("localStorage.setItem('collapsedGroups', '[]');");
});

it('draws staff avatars locally instead of calling a third-party service', function () {
    $admin = staff(Role::Admin, ['name' => 'Salma Admin']);

    $url = app(InitialsAvatarProvider::class)->get($admin);

    expect($url)->toStartWith('data:image/svg+xml;base64,')
        ->and(base64_decode(substr($url, strlen('data:image/svg+xml;base64,'))))->toContain('>S</text>');

    $this->actingAs($admin)->get('/admin')->assertOk()->assertDontSee('ui-avatars.com');
});
