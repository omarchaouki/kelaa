<?php

namespace App\Support\Security;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitizes HTML written in the admin rich editor (static pages).
 * Only formatting elements survive; scripts, event handlers, iframes,
 * styles and javascript:/data: URLs are removed.
 */
final class RichText
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return self::sanitizer()->sanitize($html);
    }

    private static function sanitizer(): HtmlSanitizer
    {
        return self::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowElement('p')
                ->allowElement('br')
                ->allowElement('strong')
                ->allowElement('b')
                ->allowElement('em')
                ->allowElement('i')
                ->allowElement('u')
                ->allowElement('s')
                ->allowElement('h2')
                ->allowElement('h3')
                ->allowElement('h4')
                ->allowElement('ul')
                ->allowElement('ol')
                ->allowElement('li')
                ->allowElement('blockquote')
                ->allowElement('hr')
                ->allowElement('a', ['href', 'title'])
                ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
                ->allowRelativeLinks()
                ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
                ->withMaxInputLength(200_000),
        );
    }
}
