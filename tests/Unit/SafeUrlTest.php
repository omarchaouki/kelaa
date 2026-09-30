<?php

use App\Support\Security\SafeUrl;

it('accepts http(s) URLs and adds https when the scheme is missing', function (string $input, string $expected) {
    expect(SafeUrl::normalize($input))->toBe($expected);
})->with([
    ['https://example.ma', 'https://example.ma'],
    ['http://example.ma/menu?x=1', 'http://example.ma/menu?x=1'],
    ['www.example.ma', 'https://www.example.ma'],
    ['  https://example.ma  ', 'https://example.ma'],
]);

it('rejects dangerous or malformed URLs', function (?string $input) {
    expect(SafeUrl::normalize($input))->toBeNull();
})->with([
    'javascript' => ['javascript:alert(1)'],
    'javascript mixed case' => ['JaVaScRiPt:alert(1)'],
    'javascript with whitespace' => ["java\tscript:alert(1)"],
    'data' => ['data:text/html;base64,PHNjcmlwdD4='],
    'vbscript' => ['vbscript:msgbox(1)'],
    'file' => ['file:///etc/passwd'],
    'protocol relative' => ['//evil.example'],
    'credentials' => ['https://user:pass@example.ma'],
    'no host' => ['https://'],
    'bare word' => ['javascript'],
    'newline' => ["https://example.ma\n.evil"],
    'empty' => [''],
    'null' => [null],
]);
