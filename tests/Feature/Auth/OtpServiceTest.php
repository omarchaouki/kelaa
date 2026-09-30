<?php

use App\Enums\OtpPurpose;
use App\Exceptions\OtpThrottled;
use App\Models\OtpCode;
use App\Services\Otp\OtpService;

beforeEach(function () {
    $this->sender = fakeOtpSender();
    $this->otp = app(OtpService::class);
    $this->phone = '+212612345678';
});

it('sends a 6 digit code and only stores its hash', function () {
    $this->otp->request($this->phone, OtpPurpose::Login, '1.2.3.4');

    $code = $this->sender->lastCodeFor($this->phone);
    $stored = OtpCode::query()->sole();

    expect($code)->toMatch('/^\d{6}$/')
        ->and($stored->code_hash)->not->toContain($code)
        ->and($stored->ip_hash)->not->toBe('1.2.3.4')
        ->and($stored->expires_at->isFuture())->toBeTrue();
});

it('accepts the right code once', function () {
    $this->otp->request($this->phone, OtpPurpose::Login, '1.2.3.4');
    $code = $this->sender->lastCodeFor($this->phone);

    expect($this->otp->verify($this->phone, OtpPurpose::Login, $code))->toBeTrue()
        ->and($this->otp->verify($this->phone, OtpPurpose::Login, $code))->toBeFalse();
});

it('rejects a wrong code and counts the attempt', function () {
    $this->otp->request($this->phone, OtpPurpose::Login, '1.2.3.4');
    $code = $this->sender->lastCodeFor($this->phone);
    $wrong = str_pad((string) (((int) $code + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);

    expect($this->otp->verify($this->phone, OtpPurpose::Login, $wrong))->toBeFalse()
        ->and(OtpCode::query()->sole()->attempts)->toBe(1);
});

it('locks a code after too many wrong attempts', function () {
    config(['otp.max_attempts' => 3]);
    $this->otp->request($this->phone, OtpPurpose::Login, '1.2.3.4');
    $code = $this->sender->lastCodeFor($this->phone);

    foreach (range(1, 3) as $attempt) {
        $this->otp->verify($this->phone, OtpPurpose::Login, $code === '000000' ? '111111' : '000000');
    }

    expect($this->otp->verify($this->phone, OtpPurpose::Login, $code))->toBeFalse();
});

it('rejects expired codes', function () {
    $this->otp->request($this->phone, OtpPurpose::Login, '1.2.3.4');
    $code = $this->sender->lastCodeFor($this->phone);

    $this->travel(config('otp.ttl_minutes') + 1)->minutes();

    expect($this->otp->verify($this->phone, OtpPurpose::Login, $code))->toBeFalse();
});

it('invalidates the previous code when a new one is requested', function () {
    $this->otp->request($this->phone, OtpPurpose::Login, '1.2.3.4');
    $first = $this->sender->lastCodeFor($this->phone);
    $this->otp->request($this->phone, OtpPurpose::Login, '1.2.3.4');
    $second = $this->sender->lastCodeFor($this->phone);

    if ($first !== $second) {
        expect($this->otp->verify($this->phone, OtpPurpose::Login, $first))->toBeFalse();
    }

    expect($this->otp->verify($this->phone, OtpPurpose::Login, $second))->toBeTrue();
});

it('does not accept a code issued for another purpose', function () {
    $this->otp->request($this->phone, OtpPurpose::BusinessClaim, '1.2.3.4');
    $code = $this->sender->lastCodeFor($this->phone);

    expect($this->otp->verify($this->phone, OtpPurpose::Login, $code))->toBeFalse();
});

it('rate limits code requests per phone number', function () {
    config(['otp.rate_limits.per_phone' => ['attempts' => 2, 'decay_minutes' => 15]]);

    $this->otp->request($this->phone, OtpPurpose::Login, '1.1.1.1');
    $this->otp->request($this->phone, OtpPurpose::Login, '2.2.2.2');
    $this->otp->request($this->phone, OtpPurpose::Login, '3.3.3.3');
})->throws(OtpThrottled::class);

it('rate limits code requests per IP address', function () {
    config(['otp.rate_limits.per_ip' => ['attempts' => 2, 'decay_minutes' => 60]]);

    $this->otp->request('+212611111111', OtpPurpose::Login, '9.9.9.9');
    $this->otp->request('+212622222222', OtpPurpose::Login, '9.9.9.9');
    $this->otp->request('+212633333333', OtpPurpose::Login, '9.9.9.9');
})->throws(OtpThrottled::class);

it('rate limits verification attempts per phone number', function () {
    config(['otp.rate_limits.verify_per_phone' => ['attempts' => 2, 'decay_minutes' => 15]]);
    $this->otp->request($this->phone, OtpPurpose::Login, '1.2.3.4');

    $this->otp->verify($this->phone, OtpPurpose::Login, '000000');
    $this->otp->verify($this->phone, OtpPurpose::Login, '000000');
    $this->otp->verify($this->phone, OtpPurpose::Login, '000000');
})->throws(OtpThrottled::class);
