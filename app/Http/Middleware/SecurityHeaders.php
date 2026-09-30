<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers on every web response, including a nonce-based CSP.
 * Registered globally so the admin panel and Livewire endpoints are covered too.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('security.csp.enabled')) {
            // Blade (@vite, Livewire, Filament) reads this nonce when rendering.
            Vite::useCspNonce();
        }

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        // Geolocation is only ever requested by our own pages ("use my location").
        $headers->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=(), payment=(), usb=(), interest-cohort=()');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age='.config('security.hsts_max_age').'; includeSubDomains');
        }

        // The local debug error page relies on inline scripts; production error pages keep the CSP.
        $debugErrorPage = config('app.debug') && $response->getStatusCode() >= 500;

        if (config('security.csp.enabled') && ! $debugErrorPage && ! $headers->has('Content-Security-Policy')) {
            $headers->set(
                config('security.csp.report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy',
                $this->policy($request, (string) Vite::cspNonce()),
            );
        }

        return $response;
    }

    private function policy(Request $request, string $nonce): string
    {
        $scriptSrc = ["'self'", "'nonce-{$nonce}'", "'unsafe-eval'"];
        $styleSrc = ["'self'", "'unsafe-inline'"];
        $connectSrc = ["'self'"];
        $imgSrc = ["'self'", 'data:', 'blob:', ...$this->tileHosts(), ...config('security.csp.img_hosts')];

        // Vite dev server (npm run dev).
        if (Vite::isRunningHot()) {
            $hot = rtrim((string) file_get_contents(public_path('hot')));
            $wsHot = preg_replace('#^http#', 'ws', $hot);
            $scriptSrc[] = $hot;
            $styleSrc[] = $hot;
            $connectSrc[] = $hot;
            $connectSrc[] = (string) $wsHot;
        }

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => $scriptSrc,
            'style-src' => $styleSrc,
            'img-src' => array_values(array_unique($imgSrc)),
            'font-src' => ["'self'", 'data:'],
            'connect-src' => $connectSrc,
            'frame-src' => ["'none'"],
            'frame-ancestors' => ["'none'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'manifest-src' => ["'self'"],
            'worker-src' => ["'self'"],
        ];

        $policy = [];

        foreach ($directives as $directive => $sources) {
            $policy[] = $directive.' '.implode(' ', $sources);
        }

        if ($request->isSecure()) {
            $policy[] = 'upgrade-insecure-requests';
        }

        if ($uri = config('security.csp.report_uri')) {
            $policy[] = "report-uri {$uri}";
        }

        return implode('; ', $policy);
    }

    /**
     * Origin of the configured map tile server, e.g. https://*.tile.openstreetmap.org
     *
     * @return list<string>
     */
    private function tileHosts(): array
    {
        $url = (string) config('directory.maps.tile_url');
        $parts = parse_url(str_replace(['{s}', '{z}', '{x}', '{y}', '{r}'], ['a', '0', '0', '0', ''], $url));

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host']) || ! in_array($parts['scheme'], ['http', 'https'], true)) {
            return [];
        }

        $host = str_contains($url, '{s}.')
            ? preg_replace('/^a\./', '*.', $parts['host'])
            : $parts['host'];

        return ["{$parts['scheme']}://{$host}"];
    }
}
