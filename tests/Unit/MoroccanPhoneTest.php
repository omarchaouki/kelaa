<?php

use App\Support\Phone\MoroccanPhone;

it('normalizes the usual ways of typing a Moroccan mobile number', function (string $input, string $expected) {
    expect(MoroccanPhone::normalize($input))->toBe($expected);
})->with([
    'local' => ['0612345678', '+212612345678'],
    'local with spaces' => ['06 12 34 56 78', '+212612345678'],
    'local with dashes and dots' => ['07-12.34-56 78', '+212712345678'],
    'international' => ['+212612345678', '+212612345678'],
    'international with spaces' => ['+212 6 12 34 56 78', '+212612345678'],
    'international with trunk zero' => ['+212 0612345678', '+212612345678'],
    '00 prefix' => ['00212712345678', '+212712345678'],
    'no plus' => ['212612345678', '+212612345678'],
    'arabic-indic digits' => ['٠٦١٢٣٤٥٦٧٨', '+212612345678'],
]);

it('rejects numbers that are not Moroccan mobiles', function (?string $input) {
    expect(MoroccanPhone::normalize($input))->toBeNull();
})->with([
    'landline' => ['0522123456'],
    'too short' => ['061234567'],
    'too long' => ['06123456789'],
    'foreign' => ['+33612345678'],
    'letters' => ['06abcdefgh'],
    'empty' => [''],
    'null' => [null],
]);

it('accepts landlines when mobile-only is disabled', function () {
    expect(MoroccanPhone::normalize('05 22 12 34 56', mobileOnly: false))->toBe('+212522123456')
        ->and(MoroccanPhone::normalize('05 22 12 34 56'))->toBeNull();
});

it('formats numbers for display and WhatsApp links', function () {
    expect(MoroccanPhone::formatNational('+212612345678'))->toBe('06 12 34 56 78')
        ->and(MoroccanPhone::forWhatsApp('+212612345678'))->toBe('212612345678');
});
