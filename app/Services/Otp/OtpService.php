<?php

namespace App\Services\Otp;

use App\Contracts\OtpSender;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpDeliveryFailed;
use App\Exceptions\OtpThrottled;
use App\Models\OtpCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Issues and checks one-time codes. Codes are stored as keyed hashes,
 * expire after config('otp.ttl_minutes') and allow a limited number of attempts.
 */
class OtpService
{
    public function __construct(
        private readonly OtpSender $sender,
    ) {}

    /**
     * @throws OtpThrottled
     * @throws OtpDeliveryFailed
     */
    public function request(string $phone, OtpPurpose $purpose, string $ip): void
    {
        $limits = config('otp.rate_limits');

        $this->hit("otp:send:phone:{$phone}", $limits['per_phone']);
        $this->hit("otp:send:phone-daily:{$phone}", $limits['per_phone_daily']);
        $this->hit('otp:send:ip:'.$this->hashIp($ip), $limits['per_ip']);

        $code = $this->generateCode();

        DB::transaction(function () use ($phone, $purpose, $ip, $code): void {
            // Only the latest code is valid.
            OtpCode::query()
                ->where('phone', $phone)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            OtpCode::query()->create([
                'phone' => $phone,
                'purpose' => $purpose,
                'code_hash' => $this->hashCode($phone, $code),
                'ip_hash' => $this->hashIp($ip),
                'expires_at' => now()->addMinutes((int) config('otp.ttl_minutes')),
            ]);
        });

        $this->sender->send($phone, $code, $purpose, app()->getLocale());
    }

    /**
     * @throws OtpThrottled
     */
    public function verify(string $phone, OtpPurpose $purpose, string $code): bool
    {
        $this->hit("otp:verify:phone:{$phone}", config('otp.rate_limits.verify_per_phone'));

        return DB::transaction(function () use ($phone, $purpose, $code): bool {
            $otp = OtpCode::query()
                ->where('phone', $phone)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($otp === null || $otp->attempts >= (int) config('otp.max_attempts')) {
                return false;
            }

            if (! hash_equals($otp->code_hash, $this->hashCode($phone, trim($code)))) {
                $otp->increment('attempts');

                return false;
            }

            $otp->forceFill(['consumed_at' => now()])->save();
            RateLimiter::clear("otp:verify:phone:{$phone}");

            return true;
        });
    }

    /**
     * @param  array{attempts: int, decay_minutes: int}  $limit
     *
     * @throws OtpThrottled
     */
    private function hit(string $key, array $limit): void
    {
        if (RateLimiter::tooManyAttempts($key, $limit['attempts'])) {
            throw new OtpThrottled(RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, $limit['decay_minutes'] * 60);
    }

    private function generateCode(): string
    {
        $length = (int) config('otp.length');

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    private function hashCode(string $phone, string $code): string
    {
        return hash_hmac('sha256', "{$phone}|{$code}", (string) config('app.key'));
    }

    private function hashIp(string $ip): string
    {
        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
