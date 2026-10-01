<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\Response;

/**
 * Only production is crawlable: staging and local copies tell robots to stay out.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        if (! app()->isProduction()) {
            return $this->text("User-agent: *\nDisallow: /\n");
        }

        $lines = ['User-agent: *'];

        foreach (['/admin', '/admin-files/', '/auth/', '/payments/', '/livewire', '/health', '/up'] as $path) {
            $lines[] = "Disallow: {$path}";
        }

        // Private or duplicate localized pages.
        foreach (Locales::supported() as $locale) {
            foreach (['dashboard', 'account', 'login', 'events'] as $path) {
                $lines[] = "Disallow: /{$locale}/{$path}";
            }
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.url('sitemap.xml');

        return $this->text(implode("\n", $lines)."\n");
    }

    private function text(string $body): Response
    {
        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
